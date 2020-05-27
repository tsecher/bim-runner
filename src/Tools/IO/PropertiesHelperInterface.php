<?php

namespace BimRunner\Tools\IO;

interface PropertiesHelperInterface {


    /**
     * Retourne la valeur de la propriété.
     *
     * @param $id
     *
     * @return mixed|null
     */
    public function getParam($id);

    /**
     * Retourne l'ensemble des paramètres.
     *
     * @return array
     */
    public function getParams();

    /**
     * Modifie un paramètre.
     *
     * @param $id
     * @param $value
     *
     * @return static
     */
    public function setParam($id, $value);

    /**
     * Modifie tous les paramtères.
     *
     * @param array $values
     *
     * @return mixed
     */
    public function setParams(array $values);

    /**
     * Retourne la valeur du state.
     *
     * @param $id
     *
     * @return mixed|null
     */
    public function getState($id);

    /**
     * Retourne l'ensemble des states.
     *
     * @return array
     */
    public function getStates();

    /**
     * Modifie un state.
     *
     * @param $id
     * @param $value
     *
     * @return static
     */
    public function setState($id, $value);

    /**
     * Set all states.
     *
     * @param $values
     *
     * @return mixed
     */
    public function setStates($values);

}
