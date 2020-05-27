<?php

namespace BimRunner\Command\Traits;

use BimRunner\Actions\Base\ActionInterface;

trait ActionRetrieverTrait {

    /**
     * Available actinos
     *
     * @var \BimRunner\Actions\Base\ActionInterface[]
     */
    protected $availableActions;

    /**
     * Return the list of actions.
     *
     * @param $actions
     */
    protected function getActionsByIds(array $actions) {
        return array_map(
          [
            $this,
            'getActionById'
          ],
          $actions);
    }

    /**
     * Retourne une action par son identifiant numéric.
     *
     * @param $id
     */
    public function getActionById($id) {
        $filtered = array_filter($this->availableActions, function(ActionInterface $action) use ($id){
           return $id == $action->getId();
        });
        
        return reset($filtered);
    }
}
