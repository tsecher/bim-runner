<?php

namespace BimRunner\Tools\IO;

class PropertiesHelper implements PropertiesHelperInterface {

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
            static::$me = new static();
        }

        return static::$me;
    }

    /**
     * Paramètres courants.
     *
     * @var array
     */
    protected $params = [];

    /**
     * State courant.
     *
     * @var array
     */
    protected $state = [];

    /**
     * Est muté
     *
     * @var bool
     */
    protected $isMute = FALSE;

    /**
     * @param bool $isMute
     */
    public function mute(): PropertiesHelperInterface {
        $this->isMute = FALSE;
        return $this;
    }

    /**
     * Retourne la valeur de la propriété.
     *
     * @param $id
     *
     * @return mixed|null
     */
    public function getParam($id) {
        if (isset($this->params[$id])) {
            return $this->params[$id];
        }

        return NULL;
    }

    /**
     * Retourne l'ensemble des paramètres.
     *
     * @return array
     */
    public function getParams() {
        return $this->params;
    }

    /**
     * Modifie un paramètre.
     *
     * @param $id
     * @param $value
     *
     * @return static
     */
    public function setParam($id, $value): PropertiesHelperInterface{
        $this->throwMute();
        $this->params[$id] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setParams(array $values): PropertiesHelperInterface{
        $this->throwMute();
        $this->params = $values;
        return $this;
    }

    /**
     * Retourne la valeur du state.
     *
     * @param $id
     *
     * @return mixed|null
     */
    public function getState($id) {
        if (isset($this->state[$id])) {
            return $this->state[$id];
        }

        return NULL;
    }

    /**
     * Retourne l'ensemble des states.
     *
     * @return array
     */
    public function getStates() {
        return $this->state;
    }

    /**
     * Modifie un state.
     *
     * @param $id
     * @param $value
     *
     * @return static
     */
    public function setState($id, $value): PropertiesHelperInterface {
        $this->state[$id] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStates($values): PropertiesHelperInterface{
        $this->state = $values;

        return $this;
    }

    /**
     * Déclenche une erreur si on est en phase d'execution.
     */
    protected function throwMute() {
        if ($this->isMute) {
            throw new \Exception('Le processus d\'execution est lancé. Vous ne pouvez pas modifier les params.');
        }
    }

}
