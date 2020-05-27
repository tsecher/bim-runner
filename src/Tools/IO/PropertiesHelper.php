<?php

namespace BimRunner\Tools\IO;

class PropertiesHelper  implements PropertiesHelperInterface{

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
    public function setParam($id, $value) {
        $this->params[$id] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setParams(array $values) {
        $this->params = $values;
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
    public function setState($id, $value) {
        $this->state[$id] = $value;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function setStates($values) {
        $this->state = $values;
    }

}
