# Bim Runner
 Bim runner est une librairie basée sur des outils Symfony qui vous 
 permet de lancer des taches automatisées.
 
 ## Principe 
La librairie fournit une application Symfony simple (RunnerApplication) qui appelle une commande Symfony.
En gros la librairie vous permet de définir une action php à jouer en ligne de commande.

Par exemple, si vous voulez créer une tâche qui clone un repo git et ensuite supprime le git, 
il vous suffit de créer un projet avec un composer.json qui définit un binaire qui appelle l'application en question.
Par exemple la commande `./clone-and-clean` va lancer le processus suivant :
1. Appeler l'application RunnerApplicatino
2. Appeler la commande Symfony `clone-and-clean`
3. Répertorier toutes les actions que vous aurez définit via une annotation
4. Vous permettre de lancer une ou plusieurs actions via une interface en ligne de commande.
Ca c'est pour le principe, on reviendra dessus par la suite.

### Organisation
La structure des runners est la suivante : 
- RunnerApplication
    - RunCommand
        - Action
            - Task (callback)
 
Un runner peut contenir plusieurs actions. Une action peut contenir plusieurs taches.
Au lancement de la RunCommand, l'utilisateur peut choisir les actions qu'il souhaite effectuer.
 

### Philosophie

#### Déroulement
Le but de bum-runner est de lancer une suite de tache successive, par exemple pour générer des projets
sur une techno particulière. 
L'idée est de lancer une suite de tâche sans que l'utilisateur aie besoin d'intervenir pour passer à la suivante.
Donc, toute les intéractions avec l'utilisateur doivent être prévues avant de lancer le processus d'execution des taches.

1. Initialisation des options avant execution de la commande (methode `ActionInterface::initOptions`)
2. Initialisation des propriétés avant execution de la commande (methode `ActionInterface::initOptions`)
3. Execution des actions dans l'ordre de poid
    3.x Pour chaque action, execution de la liste de tache.

#### Indépendance 
Sur le principe, chaque action doit pouvoir être lancée indépendamment des autres. Une tâche en revanche peut être dépendante de la précédente.

#### Les propriétés : params et state
Pour cela, la RunCommand permet à chaque Action de définir ses propres besoins via les propriétés.
Les propriétés se composent en params et state.

##### Params
Les params sont définis avant le lancement du processus d'execution. Chaque action peut définir une liste de propriété
dans la méthode `initQuestions`. Utilisez au maximum les méthodes ask, confirm et choice de l'action qui permettent de définir
automatiquement les propriétés params. Les params sont figés dès le début du processus d'execution de l'action
afin que les valeurs ne puissent pas être modifiées en court d'execution. De cette manière, les actions ne peuvent pas 
communiquer entre elles via les propriétés.

A notre que les propriétés peuvent être prédéfinis via les options en les déclarant en option de la commande via la méthode `ActionInterface::initOptions(Commmand $command)`.

##### State
Le state est un storage de valeur qui est partagé à toutes les actions au court de l'execution.
Les state peuvent être modifiés pendant le processus d'execution des actions.


 
 ## Installation
 L'installation se fait via composer de manière normal. Pour l'instant on n'a pas de packagist
 donc il est nécessaire d'ajouter le vsc à votre projet dans un premier temps :
 1. `composer config repositories.bim-runner vcs https://github.com/tsecher/bim-runner`
 2. `composer require tsecher/bim-runner`
 
 ## Comment faire?
 ### Créer une RunnerApp
 Une runner app est une application simple symfony qui permet d'appeler la ligne de commande RunCommand.
 Une RunnerApp permet de grouper les actions. 
 Par exemple, si vous travailler avec drupal, vous pouvez vous créer une RunnerApp liée à Drupal, dans laquelle vous
 allez pouvoir définir vos propres actions : 'Créer un environnement docker', 'Lancer une install', 'Ajouter un ensemble de modules'.
 
 Dans un premier temps, on crée donc la Runner App.
 1. Placez vous dans le répertoire qui va contenir l'app.
 2. Lancer la ligne de commande `bim-runner` (si vous avez installé bim-runner en global).
 3. Choisissez l'action "Générer un runner"
 4. Remplissez le questionnaire : 
    - Quel est le nom du répertoire du projet ? `drupal-builder`  
    C'est le nom du répertoire qui contiendra la RunnerApp.
    - Quel est le nom humain de votre runner ? `Drupal Builder`
    - Quel est l'id de votre runner (la commande) ? `drupal-builder`
    - Quel est le namespace de base de l'app ? `DrupalBuilder`
    - Quel est le répertiore où seront placées les Actions (par rapport à src) ? `Actions`
 
Vous avez créer votre première RunnerApp

Vous pouvez maintenant vous placer dans le répertoire de l'app que vous venez de créer, et lancer votre commande : 
'./{votre_commande}'. Vous devez voir la liste des actions à effectuer. Comme vous n'avez pas encore ajouter d'action. La liste est vide.


### Créer une action
Maintenant que vous avez créer une RunnerApp vous pouvez ajouter votre action.

1. rendez-vous dans le répertoire de votre RunnerApp
2. lancer `bim-runner` ( ou `bim-runner --actions=4 --y` pour aller plus vite )
3. remplissez le formulaire :
    - Quel est le nom de l'action ? `Créer un environnement docker pour drupal`  
    Le nom affiché de votre action. 
    - Quel est le nom de la class de l'action ? `BuildDocker`  
      Le nom de la classe de votre action.
    - Quel est le poids de l'action ? `1`  
      Le poids de l'action 
    - Quel est le namespace de l'action ? `DrupalBuilder\Actions`
    Le namespace de votre action.  
     
Un nouveau répertoire vient d'être créé dans src/Actions. Il contient la classe
de votre action.

Lancez de nouveau la commande `bim-runner` et vous devriez voir votre action dans la list.

### Créer une tache
Une tache est une callback définit dans la méthode `getTaskQueue`. Pour ajouter une tache il suffit d'ajouter une callback à la liste des taches : 
1. Créer votre tache : une méthode dans la classe de l'action. Par exemple, pour cloner un repo :   
    ```php
    // src/Actions/BuildDocker/BuildDockerAction.php
    class BuildDockerAction extends \BimRunner\Actions\Base\AbstractAction{
        use \BimRunner\Tools\Traits\GitTrait;
        // ...
        protected function cloneRepo(\BimRunner\Tools\IO\PropertiesHelperInterface $propertiesHelper){
            // On utilise le GitTrait pour cloner un repo.
            $this->cloneGitRepo('http://github.com/monrepo', 'my_project', '1.0.0');
        }
    
    }
    ```
2. Ajouter la callback à la liste des tâches
    ```php
    // src/Actions/BuildDocker/BuildDockerAction.php
    class BuildDockerAction extends \BimRunner\Actions\Base\AbstractAction{
        /**
         * {@inheritdoc}
         */
        public function getTasksQueue() {
            return [
              [$this, 'cloneRepo'],
            ];
        }    
    }
    ```
   
 Attention, l'ordre des callbacks est important.


## Les options de commandes
Par défaut il existe 4 options pour une commande de RunnerApp.

### Confirmer automatiquement : --y
`--y` vous permet de faire de valider toutes les confirmations automatiquement

### Effectuer une liste d'actions : --actions
`--actions` vous permet de prédéfinir les actions à effectuer. Les actions sont séparées par 
des virgules : ` --actions=3,5 `

### Effectuer uniquement une liste de steps : --only-steps
Vous pouvez également définir les tâches à lancer grâce au paramètre `--only-steps`. Les steps sont à indiquer par leurs
identifiant [id de l'action].[id de la tache] et séparés par des virgules : `--only-steps=3.1,3.3,4.5` 

### Démarrer depuis un step : --from-step
Vous pouvez choisir de débuter un run à partir d'un step précis via l'option `--from-step`
