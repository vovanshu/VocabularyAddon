<?php

namespace VocabularyAddon;

require_once __DIR__ . '/TraitCommon.php';

use VocabularyAddon\TraitCommon;

trait TraitGeneral
{

    use TraitCommon;

    private function getSelectVocabularies()
    {

        $response = $this->api()->search('vocabularies');
        $vocabularies = $response->getContent();
        foreach ($vocabularies as $vocabulary){
            $result[$vocabulary->id()] = $vocabulary->label();
        }
        return $result;

    }


    private function getVocabularyID($data = Null)
    {

        $params = $this->params()->fromRoute();
        $id = Null;
        if(!empty($params['id'])){
            $id = $params['id'];
        }
        if(!empty($data['o:vocabulary'])){
            $id = $data['o:vocabulary']->jsonSerialize()['o:id'];
        }
        return $id;

    }

    private function getVocabularyEntry($id)
    {
        return $this->getApiAdapterManager('vocabularies')->findEntity($id);
    }

}
