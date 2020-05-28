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
 
 ## Installation
 L'installation se fait via composer de manière normal. Pour l'instant on n'a pas de packagist
 donc il est nécessaire d'ajouter le vsc à votre projet dans un premier temps :
 1. `composer config repositories.bim-runner vcs https://github.com/tsecher/bim-runner`
 2. `composer require tsecher/bim-runner`
 
 ## Créer votre projet ligne de commande.
 
