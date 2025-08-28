<?php
namespace VocabularyAddon\Controller\Admin;

use Laminas\View\Model\ViewModel;
use Laminas\Form\Form;
use Interop\Container\ContainerInterface;
use Omeka\Api\Response;
use Omeka\Stdlib\Message;
use Omeka\Form\ConfirmForm;
use Omeka\Entity\Property;
use VocabularyAddon\Common;

class PropertyController extends \Omeka\Controller\Admin\PropertyController
{

    use Common;

    public function __construct(ContainerInterface $services, $requestedName, $options)
    {
        $this->setServiceLocator($services);
    }

    private function initForm($data = Null)
    {

        $form = $this->getForm(Form::class);

        $form
            ->add([
                'name' => 'o:vocabulary',
                'type' => 'select',
                'options' => [
                    'label' => 'Vocabulary', // @translate
                    'value_options' => $this->getSelectVocabularies(),
                ],
                'attributes' => [
                    'required' => true,
                    'id' => 'o:vocabulary',
                    'value' => $this->getVocabularyID($data)
                ],
            ]);
        $form
            ->add([
                'name' => 'o:label',
                'type' => 'text',
                'options' => [
                    'label' => 'Label', // @translate
                ],
                'attributes' => [
                    'required' => true,
                    'id' => 'o:label',
                    'value' => !empty($data['o:label']) ? $data['o:label'] : ''
                ],
            ]);
        $form
            ->add([
                'name' => 'o:local_name',
                'type' => 'text',
                'options' => [
                    'label' => 'Local name', // @translate
                ],
                'attributes' => [
                    'required' => true,
                    'id' => 'o:local_name',
                    'value' => !empty($data['o:local_name']) ? $data['o:local_name'] : ''
                ],
            ]);
        $form
            ->add([
                'name' => 'o:comment',
                'type' => 'textarea',
                'options' => [
                    'label' => 'Comment', // @translate
                ],
                'attributes' => [
                    'id' => 'o:comment',
                    'value' => !empty($data['o:comment']) ? $data['o:comment'] : ''
                ],
            ]);

        $inputFilter = $form->getInputFilter();
        $inputFilter->add([
            'name' => 'o:comment',
            'required' => false,
        ]);
        
        return $form;

    }

    public function addAction()
    {

        $form = $this->initForm();
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $property = new Property;
                if (!empty($data['o:local_name'])) {
                    $property->setLocalName($data['o:local_name']);
                }
                if (!empty($data['o:label'])) {
                    $property->setLabel($data['o:label']);
                }
                if (!empty($data['o:comment'])) {
                    $property->setComment($data['o:comment']);
                }
                if (!empty($data['o:vocabulary'])) {
                    $property->setVocabulary($this->getVocabularyEntry($data['o:vocabulary']));
                }
                $property->setOwner($this->getUserEntry($this->getCurentUserID()));
                $criteria = [
                    'vocabulary' => $data['o:vocabulary'],
                    'localName' => $data['o:local_name'],
                ];
                if($this->getAdapter('properties')->isUnique($property, $criteria)){
                    $this->getEntityManager()->persist($property);
                    $this->getEntityManager()->flush();
                    $this->getEntityManager()->refresh($property);
                    $response = new Response($property);
                    $message = new Message(
                        'Property successfully created.' // @translate
                    );
                    $this->messenger()->addSuccess($message);
                    return $this->redirect()->toRoute('admin/id', ['controller' => 'property', 'action' => 'edit', 'id' => $response->getContent()->getId()]);
                }else{
                    $this->messenger()->addError('o:local_name', new Message(
                        'The local name "%s" is already taken.', // @translate
                        $data['o:local_name']
                    ));
                    $this->messenger()->addFormErrors($form);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        return $view;

    }

    public function editAction()
    {

        $id = $this->params('id');
        $entity = $this->api()->read('properties', $id)->getContent();
        $data = $entity->jsonSerialize();
        $form = $this->initForm($data);
        if ($this->getRequest()->isPost()) {
            $data = $this->params()->fromPost();
            $form->setData($data);
            if ($form->isValid()) {
                $property = $this->getAdapter('properties')->findEntity($id);
                if (!empty($data['o:local_name'])) {
                    $property->setLocalName($data['o:local_name']);
                }
                if (!empty($data['o:label'])) {
                    $property->setLabel($data['o:label']);
                }
                if (!empty($data['o:comment'])) {
                    $property->setComment($data['o:comment']);
                }
                if (!empty($data['o:vocabulary'])) {
                    $property->setVocabulary($this->getVocabularyEntry($data['o:vocabulary']));
                }
                $this->getEntityManager()->persist($property);
                $this->getEntityManager()->flush();
                $this->getEntityManager()->refresh($property);
                $message = new Message(
                    'Property successfully saved.' // @translate
                );
                $this->messenger()->addSuccess($message);
                return $this->redirect()->refresh();
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view = new ViewModel;
        $view->setVariable('form', $form);
        $view->setVariable('entity', $entity);
        return $view;

    }

    
    public function deleteAction()
    {

        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $id = $this->params('id');
                $entity = $this->api()->read('properties', $id)->getContent();
                $response = $this->api($form)->delete('properties', $entity->id());
                if ($response) {
                    $this->messenger()->addSuccess('Property successfully deleted.'); // @translate
                }
                return $this->redirect()->toRoute('admin/id', ['controller' => 'vocabulary', 'action' => 'properties', 'id' => $entity->vocabulary()->id()]);
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/id', ['controller' => 'vocabulary']);
    }

    public function deleteConfirmAction()
    {

        $id = $this->params('id');
        $entity = $this->api()->read('properties', $id)->getContent();
        $allowDelete = True;
        if($entity->itemCount() > 0){
            $allowDelete = False;
        }
        $view = new ViewModel([
            'allowDelete' => $allowDelete,
            'resource' => $entity,
            'property' => $entity,
            'resourceLabel' => 'Property', // @translate
            'partialPath' => 'vocabulary-addon/admin/property/show-details',
        ]);
        return $view
            ->setTemplate('vocabulary-addon/admin/property/delete-confirm')
            ->setTerminal(true);
    }

}
