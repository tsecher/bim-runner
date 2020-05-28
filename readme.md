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
 

 
 ## Installation
 L'installation se fait via composer de manière normal. Pour l'instant on n'a pas de packagist
 donc il est nécessaire d'ajouter le vsc à votre projet dans un premier temps :
 1. `composer config repositories.bim-runner vcs https://github.com/tsecher/bim-runner`
 2. `composer require tsecher/bim-runner`
 
 ## Créer votre RunnerApp.
 ### RunnerApp
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
