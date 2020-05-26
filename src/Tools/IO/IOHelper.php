<?php

namespace BimRunner\Tools\IO;

use BimRunner\Command\RunCommand;
use BimRunner\Tools\Traits\StringTrait;
use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;
use function Symfony\Component\String\u;

class IOHelper implements IOHelperInterface {

    use StringTrait;

    /**
     * The command.
     *
     * @var \BimRunner\Command\RunCommand
     */
    private $command;

    /**
     * Question Helper
     *
     * @var \Symfony\Component\Console\Helper\QuestionHelper
     */
    private $helper;

    /**
     * @var \Symfony\Component\Console\Input\InputInterface
     */
    private $input;

    /**
     * @var \Symfony\Component\Console\Output\OutputInterface
     */
    private $output;

    /**
     * Singleton
     *
     * @var
     */
    protected static $me;

    /**
     * Retourne le singleton.
     *
     * @return static
     *   Le singleton.
     */
    public static function me() {
        if (!isset(static::$me)) {
            throw new \Exception('Singleton non créé. Utilisez static::create');
        }

        return static::$me;
    }

    /**
     * Create singleton.
     *
     * @param \BimRunner\Command\RunCommand $command
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    public static function create(RunCommand $command, InputInterface $input, OutputInterface $output): IOHelperInterface {
        static::$me = new static($command, $input, $output);

        return static::$me;
    }

    /**
     * IOHelper constructor.
     *
     * @param \BimRunner\Command\RunCommand $command
     * @param \Symfony\Component\Console\Input\InputInterface $input
     * @param \Symfony\Component\Console\Output\OutputInterface $output
     */
    private function __construct(RunCommand $command, InputInterface $input, OutputInterface $output) {
        $this->command = $command;
        $this->helper = $this->command->getHelper('question');
        $this->input = $input;
        $this->output = $output;

        $this->loadStyles($output);
    }

    /**
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function getInput(): \Symfony\Component\Console\Input\InputInterface {
        return $this->input;
    }

    /**
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput(): \Symfony\Component\Console\Output\OutputInterface {
        return $this->output;
    }

    /**
     * Define styles
     */
    protected function loadStyles() {
        // Récupération de la config graphique de la console.
        $configPath = $this->command->getFileHelper()
            ->getAppDir() . 'Resources/config/io.yml';
        if (!file_exists($configPath)) {
            $configPath = __DIR__ . '/../../../Resources/config/io.yml';
        }
        $ioConfig = Yaml::parseFile($configPath);
        foreach ($ioConfig as $configName => $config) {
            $this->output->getFormatter()->setStyle($configName,
              new OutputFormatterStyle(
                isset($config['foreground']) ? $config['foreground'] : 'default',
                isset($config['background']) ? $config['background'] : 'default',
                isset($config['options']) ? $config['options'] : [])
            );
        }
    }

    /**
     * Prompt user.
     *
     * @param string $question
     * @param string $default
     *
     * @return string
     */
    public function ask(string $questionTitle, $default = NULL, $validateCallbacks = []) {
        $question = new Question(
          $this->s('<question>@question </question>',
            ['@question' => $questionTitle]
          ), $default
        );

        $value = $this->helper->ask(
          $this->input,
          $this->output,
          $question
        );

        // Validate.
        try {
            $this->validate($value, $validateCallbacks);
        }
        catch (\Exception $e) {
            $this->error($e->getMessage());
            $value = $this->ask($questionTitle, $default, $validateCallbacks);
        }

        return $value;
    }

    /**
     * Confirm
     */
    public function confirm(string $questionTitle) {
        if (is_null($this->input->getOption('y'))) {
            $this->info($questionTitle);
            $result = TRUE;
        }
        else {
            $question = new ConfirmationQuestion(
              $this->s('<confirm>@question [Y/n] </confirm>',
                ['@question' => $questionTitle]
              )
            );

            $result = $this->helper->ask($this->input, $this->output, $question);
        }

        return $result;
    }

    /**
     * @param $title
     */
    public function section($sectionTitle) {
        $this->output->writeln('');
        $this->output->writeln(
          $this->s('<section>======== @title =========</section>',
            ['@title' => $sectionTitle]
          )
        );
    }

    /**
     * Choice.
     *
     * @param $questionTitle
     * @param $options
     * @param $default
     *
     * @return mixed
     */
    public function choice($questionTitle, $options, $default) {
        $question = new ChoiceQuestion(
          $this->s('<question>======== @title ========= </question>',
            ['@title' => $questionTitle]
          ), $options, $default);

        return $this->helper->ask($this->input, $this->output, $question) ?: $default;
    }

    /**
     * Show info.
     *
     * @param $infoText
     */
    public function info($infoText) {
        $this->output->writeln(
          $this->s('<info>@infoText</info>',
            ['@infoText' => $infoText]
          )
        );
    }

    /**
     * Show message with style.
     *
     * @param $message
     * @param string $style
     */
    public function message($message, $style = 'info') {
        $this->output->writeln(
          $this->s('<@style>@message</@style>',
            [
              '@message' => $message,
              '@style'   => $style,
            ]
          )
        );
    }

    /**
     * Affiche une commande.
     *
     * @param $infoText
     */
    public function command($infoText) {
        $this->output->writeln(
          $this->s('<command>@infoText<command>',
            ['@infoText' => $infoText]
          )
        );
    }

    /**
     * Show error.
     *
     * @param string $message
     */
    public function error(string $message) {
        $this->output->writeln(
          $this->s('<error>@message</error>',
            ['@message' => $message]
          )
        );
    }

    /**
     * Show step.
     *
     * @param string $message
     */
    public function step(string $message) {
        $this->output->writeln('');
        $this->output->writeln(
          $this->s('<step>========= @message</step>',
            ['@message' => $message]
          )
        );
    }

    /**
     * Check if value is not empty.
     *
     * @param string $value
     *
     * @return string
     *
     * @throws \Exception
     */
    public function notEmpty($value) {
        if (empty($value)) {
            throw new \Exception('Valeur incorrecte (ne doit pas être vide)');
        }

        return $value;
    }

    /**
     * Check if value is snake.
     *
     * @param string $value
     *
     * @return string
     *
     * @throws \Exception
     */
    public function isSnake($value) {
        $snake = u($value)->snake();
        if ($snake != $value) {
            throw new \Exception($this->s('Valeur doit être un snake (Vous vouliez dire "@snake")', ['@snake' => $snake]));
        }

        return $value;
    }

    /**
     * Validate value.
     *
     * @param $value
     * @param array $validateCallbacks
     */
    protected function validate($value, array $validateCallbacks) {
        foreach ($validateCallbacks as $callback) {
            // Object method.
            if (method_exists($this, $callback)) {
                $this->$callback($value);
            }
            else {
                call_user_func($callback, $value);
            }
        }
    }

}
