<?php
namespace VocabularyAddon\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Omeka\Form\VocabularyForm;
use Omeka\Form\VocabularyUpdateForm;
use Omeka\Mvc\Exception;
use Omeka\Stdlib\RdfImporter;
use Omeka\Stdlib\Message;
use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;
// use Laminas\View\Model\JsonModel;
// use Laminas\View\Model\ViewModel;
use Interop\Container\ContainerInterface;
use VocabularyAddon\Common;

class VocabularyControllerDelegator extends \Omeka\Controller\Admin\VocabularyController
{

    use Common;

    public function __construct(ContainerInterface $services, $name, $callback, $options)
    {

        $this->setServiceLocator($services);
        parent::__construct($services->get('Omeka\RdfImporter'));

    }

    public function editAction()
    {
        $vocabulary = $this->api()->read('vocabularies', $this->params('id'))->getContent();
        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => $vocabulary]);
        if ($vocabulary->isPermanent() && !$this->getSets('editall')) {
            throw new Exception\PermissionDeniedException('Cannot edit a permanent vocabulary');
        }
        $form->get('vocabulary-file')->add([
            'name' => 'noupdate',
            'type' => 'checkbox',
            'options' => [
                'label' => 'No update', // @translate
                '' // ''
            ],
            'attributes' => [
                'value' => True,
            ],
        ]);
        $form->getInputFilter()->get('vocabulary-file')->add([
            'name' => 'noupdate',
            'required' => false,
        ]);

        $data = [
            'vocabulary-info' => [
                'o:label' => $vocabulary->label(),
                'o:comment' => $vocabulary->comment(),
                'o:namespace_uri' => $vocabulary->namespaceUri(),
            ],
        ];
        $form->setData($data);
        $view = new ViewModel;
        $view->setVariable('vocabulary', $vocabulary);
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = array_merge_recursive(
                $request->getPost()->toArray(),
                $request->getFiles()->toArray()
            );
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->api($form)->update('vocabularies', $this->params('id'), $data['vocabulary-info'], [], ['isPartial' => true]);
                $strategy = null;
                $options = [
                    'format' => $data['vocabulary-file']['format'],
                    'lang' => $data['vocabulary-advanced']['lang'],
                    'label_property' => $data['vocabulary-advanced']['label_property'],
                    'comment_property' => $data['vocabulary-advanced']['comment_property'],
                ];
                if ('upload' === $data['vocabulary-file']['import_type']) {
                    $strategy = 'file';
                    $options['file'] = $data['vocabulary-file']['file']['tmp_name'];
                } elseif ('url' === $data['vocabulary-file']['import_type']) {
                    $strategy = 'url';
                    $options['url'] = $data['vocabulary-file']['url'];
                }
                if (null === $strategy || !empty($data['vocabulary-file']['noupdate'])) {
                    $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                }
                try {
                    $diff = $this->rdfImporter->getDiff($strategy, $vocabulary->namespaceUri(), $options);
                    $this->messenger()->addSuccess('Please review these changes before you accept them.'); // @translate
                    $form = $this->getForm(VocabularyUpdateForm::class);
                    $form->setAttribute('action', $this->url()->fromRoute(null, ['action' => 'update'], true));
                    $form->get('diff')->setValue(json_encode($diff));
                    $view->setVariable('diff', $diff);
                    $view->setTemplate('omeka/admin/vocabulary/update');
                } catch (\Exception $e) {
                    $this->messenger()->addError($e->getMessage());
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

    public function addAction()
    {

        $form = $this->getForm(VocabularyForm::class, ['vocabulary' => Null]);
        $form->getInputFilter()->get('vocabulary-file')->add([
            'name' => 'import_type',
            'required' => false,
        ]);
        $form->getInputFilter()->get('vocabulary-file')->add([
            'name' => 'format',
            'required' => false,
        ]);
        $view = new ViewModel;
        $request = $this->getRequest();
        if ($request->isPost()) {
            $post = $request->getPost()->toArray();
            $form->setData($post);
            if ($form->isValid()) {
                $data = $form->getData();
                $response = $this->api($form)->create('vocabularies', $data['vocabulary-info']);
                if(!empty($response)){
                    $this->messenger()->addSuccess('Vocabulary successfully updated'); // @translate
                    return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        $view->setVariable('form', $form);
        return $view;
    }

}
