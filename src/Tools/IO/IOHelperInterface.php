<?php

namespace BimRunner\Tools\IO;

use Symfony\Component\Console\Formatter\OutputFormatterStyle;
use Symfony\Component\Console\Question\ChoiceQuestion;
use Symfony\Component\Console\Question\ConfirmationQuestion;
use Symfony\Component\Console\Question\Question;
use Symfony\Component\Yaml\Yaml;

interface IOHelperInterface {

    /**
     * @return \Symfony\Component\Console\Input\InputInterface
     */
    public function getInput(): \Symfony\Component\Console\Input\InputInterface;

    /**
     * @return \Symfony\Component\Console\Output\OutputInterface
     */
    public function getOutput(): \Symfony\Component\Console\Output\OutputInterface;

    /**
     * Prompt user.
     *
     * @param string $question
     * @param string $default
     *
     * @return string
     */
    public function ask(string $questionTitle, $default = NULL, $validateCallbacks = []);

    /**
     * Confirm.
     */
    public function confirm(string $questionTitle);

    /**
     * Affiche un titre de section.
     * @param $title
     */
    public function section($sectionTitle);

    /**
     * Choice.
     *
     * @param $questionTitle
     * @param $options
     * @param $default
     *
     * @return mixed
     */
    public function choice($questionTitle, $options, $default);

    /**
     * Show info.
     *
     * @param $infoText
     */
    public function info($infoText);

    /**
     * Show message with style.
     *
     * @param $message
     * @param string $style
     */
    public function message($message, $style = 'info');

    /**
     * Affiche une commande.
     *
     * @param $infoText
     */
    public function command($infoText);

    /**
     * Show error.
     *
     * @param string $message
     */
    public function error(string $message);

    /**
     * Show step.
     *
     * @param string $message
     */
    public function step(string $message);
}
