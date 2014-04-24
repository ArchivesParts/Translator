<?php

/**
 * MessageController.php
 *
 * PHP Version 5.3
 *
 *
 * LICENCE
 *
 * L'ensemble de ce code relève de la législation française et internationale
 * sur le droit d'auteur et la propriété intellectuelle. Tous les droits de
 * reproduction sont réservés, y compris pour les documents téléchargeables et
 * les représentations iconographiques et photographiques. La reproduction de
 * tout ou partie de ce code sur quelque support que ce soit est formellement
 * interdite sauf autorisation écrite émanant de la société DIGITALEO.
 *
 * @category Digitaleo
 * @package  Application.Modules.Editor.Controllers
 * @author   Gregory ANNE <ganne@digitaleo.com>
 * @license  http://www.digitaleo.net/licence.txt Digitaleo Licence
 * @link     http://www.digitaleo.net
 */

/**
 * Description de la classe : Gestion des site mobile
 *
 * @category Digitaleo
 * @package  Application.Modules.Editor.Controllers
 * @author   Gregory ANNE <ganne@digitaleo.com>
 * @license  http://www.digitaleo.net/licence.txt Digitaleo Licence
 * @link     http://www.digitaleo.net
 */
class Editor_MessageController extends Zend_Controller_Action
{

    /**
     * Affichage de la liste des sites et de leurs pages
     *
     * @return void
     */
    public function siteListAction()
    {
        $form = new Zend_Form();
        //un champ de saisie éditable avec le nom du site
        $idInput = new Zend_Form_Element_Text('id', array('label' => 'Id'));
        //un champ de saisie éditable avec le nom du site
        $nameInput = new Zend_Form_Element_Text('name', array('label' => 'Name'));
        //un champ de saisie éditable avec le nom du site
        $labelInput = new Zend_Form_Element_Text('label', array('label' => 'Label'));
        //une champspour l'etat du site online/offline
        $onlineInput = new Zend_Form_Element_Select('online', array('label' => 'Site online'));
        $onlineInput->addMultiOption('', 'Etat');
        $onlineInput->addMultiOption('1', 'Online');
        $onlineInput->addMultiOption('0', 'Offline');
        //un sélecteur d’utilisateur lié (uniquement accessible en mode administrateur)
        //@Todo Ajouter une jointure pour n'afficher que les user ayant un site
        //Construction de la liste des sites existant
        $serviceUser = Dm_Session::GetServiceFactory()->getUserService();
        /* @var $serviceUser Service_Api_Handler_User_Interface */
        $userList = $serviceUser->usersRead();
        $members = array("Propriétaire");
        /* @var $userList Service_Api_Object_ObjectList */
        foreach ($userList->list as $member) {
            /* @var $member Service_Api_Object_User */
            $members[$member->id] = $member->firstName . " " . $member->name;
        }

        //Construction de la liste des sites existant en fonction du role de l'utilisateur
        $user = Dm_Session::GetConnectedUser();
        $userInput = new Zend_Form_Element_Select('userId');
        $userInput->setDisableTranslator(true);
        $userInput->setLabel(ucfirst($this->view->translate('user')));
        $userInput->addMultiOptions((array) $members);
        if ($user->role != Service_Api_Object_User::ROLE_AMDIN &&
            $user->role != Service_Api_Object_User::ROLE_APPLICATION) {
            $userInput->setValue($user->id);
            $userInput->setOptions(array('disabled' => true));
        }
        //Mode du site
        $typeInput = new Zend_Form_Element_Select('mode', array('label' => 'Mode'));
        $typeInput->addMultiOption('', 'Mode');
        $typeInput->addMultiOption('original', 'Original');
        $typeInput->addMultiOption('duplicated', 'Dupliqué');

        /**
         * Bouton de validation du formulaire
         */
        $submitInput = new Zend_Form_Element_Submit('submit');
        $submitInput->setLabel(ucfirst($this->view->translate('filter')));
        $submitInput->setOptions(array('class' => 'submit-button'));

        /**
         * Bouton de reset du formulaire
         */
        $resetInput = new Zend_Form_Element_Reset('reset');
        $resetInput->setLabel(ucfirst($this->view->translate('reset')));
        $resetInput->setOptions(array('class' => 'cancel-button'));

        $form->addElements(
            array($idInput, $nameInput, $labelInput, $userInput, $typeInput, $onlineInput, $resetInput, $submitInput)
        );
        $form->setDecorators(array(new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_Form()));
        $form->setElementDecorators(
            array(
                'ViewHelper', 'Label',
                new Dm_Form_Decorator_ShortErrors(),
            )
        );

        $submitInput->removeDecorator('Label');
        $resetInput->removeDecorator('Label');

        $this->view->form = $form;

        /* @var Editor_Model_Message_Table */
        $messageTable = new Editor_Model_Message_Table();
        $select = $messageTable
            ->select(Editor_Model_Component_Table::SELECT_WITHOUT_FROM_PART)
            ->from(array('m' => 'messages'))->order('m.messageId DESC')
            ->join(array('s' => 'sites'), 'm.messageId=s.messageId', null);

        $form->populate($this->getRequest()->getParams());
        $messageId = $form->getValue('id');
        $mode = $form->getValue('mode');
        $owner = $form->getValue('userId');
        $online = $form->getValue('online');
        $name = $form->getValue('name');
        $label = $form->getValue('label');
        if ($messageId) {
            $select->where('m.messageId=' . $messageId);
        }
        $select->where('m.type=?', Editor_Model_Message_Row::SITE_MOBILE);
        //Filtre sur le propriétaire du site
        if ($owner) {
            $select->joinLeft(array('us' => 'message_users'), 'us.messageId=m.messageId', null)
                ->where('us.userId=' . $owner);
        }
        //Filtre sur les site online/offline
        if ($online != '') {
            $select->where('s.online=' . $online);
        }
        //Filtre sur le nom du site
        if ($name) {
            $select->where("s.name LIKE '%" . $name . "%'");
        }
        //Filtre sur le label du site
        if ($label) {
            $select->where("s.label LIKE '%" . $label . "%'");
        }
        //Filtre sur le mode du site
        if ($mode) {
            $select->where('m.mode=?', $mode);
        }

        $paginator = Zend_Paginator::factory($select);

        $page = $this->getRequest()->getParam('page', 1);
        $paginator->setCurrentPageNumber($page);
        $this->view->paginator = $paginator;
    }

    /**
     * Affichage de la liste des messages et de leurs pages
     *
     * @return void
     */
    public function messageListAction()
    {
        $form = new Zend_Form();
        //un champ de saisie éditable avec le nom du site
        $idInput = new Zend_Form_Element_Text('id', array('label' => 'Id'));

        //un sélecteur d’utilisateur lié (uniquement accessible en mode administrateur)
        //@Todo Ajouter une jointure pour n'afficher que les user ayant un site
        //Construction de la liste des sites existant
        $serviceUser = Dm_Session::GetServiceFactory()->getUserService();
        $userList = $serviceUser->usersRead();
        $members = array("Propriétaire");

        if ($userList->size > 0) {
            /* @var $userList Service_Api_Object_ObjectList */
            foreach ($userList->list as $member) {
                /* @var $member Service_Api_Object_User */
                $members[$member->id] = $member->firstName . " " . $member->name;
            }

            //Construction de la liste des sites existant en fonction du role de l'utilisateur
            $user = Dm_Session::GetConnectedUser();
            $userInput = new Zend_Form_Element_Select('userId', array('label' => 'Users'));
            $userInput->addMultiOptions((array) $members);
            if ($user->role != Service_Api_Object_User::ROLE_AMDIN &&
                $user->role != Service_Api_Object_User::ROLE_APPLICATION) {
                $userInput->setValue($user->id);
                $userInput->setOptions(array('disabled' => true));
            }
        }
        //Type du message
        $typeInput = new Zend_Form_Element_Select('type', array('label' => 'Type'));
        $typeInput->addMultiOption('', 'All');
        $typeInput->addMultiOption(Editor_Model_Message_Row::SMS, 'SMS');
        $typeInput->addMultiOption(Editor_Model_Message_Row::EMAIL, 'EMAIL');
        $typeInput->addMultiOption(Editor_Model_Message_Row::SITE_MOBILE, 'SITE');
        //@todo ajouter ici l'ensemble de type de message quand implementé
        //Mode de creation du message
        $modeInput = new Zend_Form_Element_Select('mode', array('label' => 'Mode'));
        $modeInput->addMultiOption('', 'Mode');
        $modeInput->addMultiOption('original', 'Original');
        $modeInput->addMultiOption('duplicated', 'Dupliqué');

        $submitInput = new Zend_Form_Element_Submit('submit');
        $submitInput->setOptions(array("class" => "submit-button"));

        /**
         * Bouton de validation du formulaire
         */
        $submitInput = new Zend_Form_Element_Submit('submit');
        $submitInput->setLabel(ucfirst($this->view->translate('filter')));
        $submitInput->setOptions(array('class' => 'submit-button'));

        /**
         * Bouton de reset du formulaire
         */
        $resetInput = new Zend_Form_Element_Reset('reset');
        $resetInput->setLabel(ucfirst($this->view->translate('reset')));
        $resetInput->setOptions(array('class' => 'cancel-button'));

        $form->addElements(
            array($idInput, $userInput, $typeInput, $modeInput, $resetInput, $submitInput)
        );
        $form->setDecorators(array(new Zend_Form_Decorator_FormElements(), new Zend_Form_Decorator_Form()));
        $form->setElementDecorators(
            array(
                'ViewHelper', 'Label',
                new Dm_Form_Decorator_ShortErrors(),
            )
        );

        $submitInput->removeDecorator('Label');
        $resetInput->removeDecorator('Label');

        $this->view->form = $form;

        $messageTable = new Editor_Model_Message_Table();
        $select = $messageTable
            ->select(Editor_Model_Component_Table::SELECT_WITHOUT_FROM_PART)
            ->from(array('m' => 'messages'))
            ->order('m.messageId DESC');

        $form->populate($this->getRequest()->getParams());
        $messageId = $form->getValue('id');
        $mode = $form->getValue('mode');
        $owner = $form->getValue('userId');
        $type = $form->getValue('type');
        $this->view->type = $type;

        if ($type) {
            $select->where('type=?', $type);
        }

        if ($messageId) {
            $select->where('m.messageId=' . $messageId);
        }

        if ($mode) {
            $select->where('m.mode=?', $mode);
        }

        //@todo a refaire avec les findDependante
        //Filtre sur le propriétaire du site
        if ($owner) {
            $select->joinLeft(array('us' => 'message_users'), 'us.messageId=m.messageId', null)
                ->where('us.userId=' . $owner);
        }

        $paginator = Zend_Paginator::factory($select);

        $page = $this->getRequest()->getParam('page', 1);
        $paginator->setCurrentPageNumber($page);
        $this->view->paginator = $paginator;
    }

    /**
     * Action d'edition d'un site
     *
     * @return void
     */
    public function siteEditAction()
    {
        //----------------------------------------------------------------------------------------------------
        //Construction du formulaire pour ajouter un nouveau site
        //----------------------------------------------------------------------------------------------------
        $form = new Zend_Form();
        //un champ de saisie cache avec le'identifiant  du site
        $siteInput = new Zend_Form_Element_Hidden('messageId', array());
        $siteInput->addFilter(new Zend_Filter_Null());
        //un champ de saisie éditable avec le nom du site
        $nameInput = new Zend_Form_Element_Text('name', array('label' => 'Name', 'required' => true));
        //un champ de saisie éditable avec le nom du site
        $labelInput = new Zend_Form_Element_Text('label', array('label' => 'Label', 'required' => true));
        //un sélecteur d’utilisateur lié (uniquement accessible en mode administrateur)
        $currentUser = Dm_Session::GetConnectedUser();
        //Construction de la liste des sites existant
        if ($currentUser->role == Service_Api_Object_User::ROLE_AMDIN) {
            $members = array();
            $serviceUser = Dm_Session::GetServiceFactory()->getUserService();
            $userList = $serviceUser->usersRead();
            /* @var $userList Service_Api_Object_ObjectList */
            foreach ($userList->list as $member) {
                /* @var $member Service_Api_Object_User */
                $members[$member->id] = $member->firstName . " " . $member->name;
            }
            $userInput = new Zend_Form_Element_Select('userId');
            $userInput->setLabel(ucfirst($this->view->translate('user')));
            $userInput->setDisableTranslator(true);
            $userInput->addMultiOptions((array) $members);
            $userInput->setValue($currentUser->id);

            //Type du site
            $typeInput = new Zend_Form_Element_Select('mode', array('label' => 'Mode', 'required' => true));
            $typeInput->addMultiOption('original', 'Original');
            $typeInput->addMultiOption('duplicated', 'Dupliqué');
        } else {
            $userInput = new Zend_Form_Element_Hidden('userId', array('value' => Dm_Session::GetConnectedUser()->id));
            $typeInput = new Zend_Form_Element_Hidden('mode', array());
        }

        //un sélecteur de look
        $lookHandler = new Editor_Model_Look_Table();
        $lookSelect = $lookHandler->select()->order('label');
        $lookSet = $lookHandler->fetchAll($lookSelect)->toMultiSelectOption();
        $lookInput = new Zend_Form_Element_Select('lookId', array('label' => 'Look'));
        $lookInput->addMultiOption(null, "select a style");
        $lookInput->setFilters(array(new Zend_Filter_Null()));
        $lookInput->addMultiOptions($lookSet);

        //Version du site
        $jqmVersionInput = new Zend_Form_Element_Select('jqmVersion', array('label' => 'Version', 'required' => true));
        $jqmVersionInput->setMultiOptions(Editor_Model_Site_Table::getJqueryVersion());

        //un champ pour définir la date de fin de validité du site (optionnel)
        $startDate = new ZendX_JQuery_Form_Element_DatePicker("scheduleStartDate", array("label" => "Start date"));
        $startDate->setjQueryParams(
            array(
                'duration' => 'fast',
                'showAnim' => 'slideDown',
                'showOn' => 'button',
                'buttonImage' => "/pictures/buttons/calendar.gif",
                'buttonImageOnly' => true,
                'dateFormat' => 'yy-mm-dd'
            )
        );
        $stopDate = new ZendX_JQuery_Form_Element_DatePicker("scheduleStopDate", array("label" => "End date"));
        $stopDate->setjQueryParams(
            array(
                'duration' => 'fast',
                'showAnim' => 'slideDown',
                'showOn' => 'button',
                'buttonImage' => "/pictures/buttons/calendar.gif",
                'buttonImageOnly' => true,
                'dateFormat' => 'yy-mm-dd'
            )
        );
        $form->addElements(
            array($siteInput, $nameInput, $labelInput, $userInput, $startDate,
                $stopDate, $jqmVersionInput, $lookInput, $typeInput)
        );

        $form->setElementDecorators(array('ViewHelper', new Dm_Form_Decorator_ShortErrors()));
        $startDate->setDecorators(array('UiWidgetElement'));
        $startDate->setFilters(array(new Zend_Filter_Null()));
        $stopDate->setDecorators(array('UiWidgetElement'));
        $stopDate->setFilters(array(new Zend_Filter_Null()));
        //Bouton submit
        $submitInput = new Zend_Form_Element_Submit('submit', array('class' => 'validate-button'));
        $submitInput->clearDecorators();
        $submitInput->addDecorator(new Zend_Form_Decorator_ViewHelper());
        $form->addElement($submitInput);
        $form->populate($this->getRequest()->getParams());
        //----------------------------------------------------------------------------------------------------
        // FIN gestion du form
        //----------------------------------------------------------------------------------------------------

        /* @var $site Editor_Model_Site_Row */

        //------------------------------------------------------------
        // Traitement de la requette d'insertion
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                //Traitement des infos
                $messageId = $siteInput->getValue();
                $messageHandler = new Editor_Model_Message_Table();
                if (!is_null($messageId)) {
                    $message = $messageHandler->fetchRow('messageId=' . $messageId);
                    if (isset($message)) {
                        $userInput->setValue($message->getUser()->id);
                    } else {
                        $this->_helper->FlashMessenger->addMessage(
                            array('error' => 'Site ID unknown')
                        );
                        $this->_helper->redirector()->redirectAndExit();
                    }
                } else {
                    $message = Editor_Service_Message::CreateMessage(
                            rand(0, 1000), $userInput->getValue(), Editor_Model_Message_Row::SITE_MOBILE
                    );
                    $siteInput->setValue($message->getConcreteMessage()->messageId);
                }
                /* @var $message Editor_Model_Message_Row */

                $site = $message->getConcreteMessage();
                $site->setFromArray($form->getValues());
                $site->save();

                $form->populate($site->toArray());
                if (!is_null($site->messageId)) {
                    $this->view->messageId = $site->messageId;
                }
            } else {
                $entries = $form->getMessages();
                foreach ($entries as $input => $msgs) {
                    foreach ($msgs as $message) {
                        $this->_helper->FlashMessenger->addMessage(
                            array('error' => $message . ' [' . $input . ']')
                        );
                    }
                }
            }
        }
        $this->view->form = $form;
    }

    /**
     * Action d'edition d'un message
     *
     * @return void
     */
    public function messageEditAction()
    {
        //----------------------------------------------------------------------------------------------------
        //Construction du formulaire pour ajouter un nouveau site
        //----------------------------------------------------------------------------------------------------
        $form = new Zend_Form();
        //un champ de saisie cache avec le'identifiant  du site
        $messageInput = new Zend_Form_Element_Hidden('messageId', array());
        $messageInput->addFilter(new Zend_Filter_Null());

        //un sélecteur d’utilisateur lié (uniquement accessible en mode administrateur)
        $currentUser = Dm_Session::GetConnectedUser();

        //Construction de la liste des sites existant
        if ($currentUser->role == Service_Api_Object_User::ROLE_AMDIN) {
            $members = array();
            $serviceUser = Dm_Session::GetServiceFactory()->getUserService();
            $userList = $serviceUser->usersRead();

            /* @var $userList Service_Api_Object_ObjectList */
            foreach ($userList->list as $member) {
                /* @var $member Service_Api_Object_User */
                $members[$member->id] = $member->firstName . " " . $member->name;
            }
            $userInput = new Zend_Form_Element_Select('userId');
            $userInput->setLabel(ucfirst($this->view->translate('user')));
            $userInput->setDisableTranslator(true);
            $userInput->addMultiOptions((array) $members);
            $userInput->setValue($currentUser->id);

            //Type du site
            $modeInput = new Zend_Form_Element_Select('mode', array('label' => 'Mode', 'required' => true));
            $modeInput->addMultiOption('original', 'Original');
            $modeInput->addMultiOption('duplicated', 'Dupliqué');
        } else {
            $userInput = new Zend_Form_Element_Hidden('userId', array('value' => Dm_Session::GetConnectedUser()->id));
            $modeInput = new Zend_Form_Element_Hidden('mode', array());
        }

        $form->addElements(
            array($messageInput, $userInput, $modeInput)
        );

        $form->setElementDecorators(array('ViewHelper', new Dm_Form_Decorator_ShortErrors()));

        //Bouton submit
        $submitInput = new Zend_Form_Element_Submit('submit', array('class' => 'submit-button'));
        $submitInput->clearDecorators();
        $submitInput->addDecorator(new Zend_Form_Decorator_ViewHelper());
        $form->addElement($submitInput);
        $form->populate($this->getRequest()->getParams());
        //----------------------------------------------------------------------------------------------------
        // FIN gestion du form
        //----------------------------------------------------------------------------------------------------
        //------------------------------------------------------------
        // Traitement de la requette d'insertion
        if ($this->getRequest()->isPost()) {
            if ($form->isValid($this->getRequest()->getPost())) {
                //Traitement des infos
                $messageId = $messageInput->getValue();
                $messageHandler = new Editor_Model_Message_Table();
                if (!is_null($messageId)) {
                    try {
                        $abstractMessage = $messageHandler->fetchRow('messageId=' . $messageId);
                        /** @var $abstractMessage Editor_Model_Message_Row */
                        if (isset($abstractMessage)) {
                            $userInput->setValue($abstractMessage->getUser()->id);
                        } else {
                            $this->_helper->FlashMessenger->addMessage(
                                array('error' => 'Message ID unknown')
                            );
                            $this->_helper->redirector()->redirectAndExit();
                        }
                    }catch (Exception $e) {

                        $userSiteTable = new Editor_Model_Message_User_Table();
                        $userSiteTable->createRow(
                            array(
                                'userId' => $userInput->getValue(),
                                'messageId' => $messageId
                            )
                        )->save();
                    }
                } else {
                    $type = $this->_getParam('type', Editor_Model_Message_Row::SMS);
                    $abstractMessage = Editor_Service_Message::CreateMessage(
                            rand(0, 1000), $userInput->getValue(), $type
                    );

                    $messageInput->setValue($abstractMessage->messageId);
                }
                /* @var $message Editor_Model_Message_Row */

                $message = $abstractMessage->getConcreteMessage();
                $message->setFromArray($form->getValues());
                $message->save();

                $form->populate($message->toArray());
                if (!is_null($abstractMessage->messageId)) {
                    $this->view->messageId = $abstractMessage->messageId;
                    $this->view->messageType = $abstractMessage->type;
                }
            } else {
                $entries = $form->getMessages();
                foreach ($entries as $input => $msgs) {
                    foreach ($msgs as $message) {
                        $this->_helper->FlashMessenger->addMessage(
                            array('error' => $message . ' [' . $input . ']')
                        );
                    }
                }
            }
        }
        $this->view->form = $form;
    }

    /**
     * Suppression d'un message et de tous ce a quoi il est lié
     *
     * messageId - Identifiant du message à supprimer
     *
     * @return void
     */
    public function deleteAction()
    {
        $messageHandler = new Editor_Model_Message_Table();
        $messageHandler->getAdapter()->beginTransaction();
        try {
            $messageId = $this->getRequest()->getParam('messageId');
            if (!isset($messageId)) {
                throw new Exception($this->view->translate('Cannot delete a message without a valid identifier'));
            }

            $message = $messageHandler->find($messageId)->current();
            $type = $message->type;
            if ($message) {
                $message->delete();
                $messageHandler->getAdapter()->commit();
                $paths = Zend_Registry::get('paths');
                $messageDir = $paths['upload'] . "$messageId/";
                if (is_dir($messageDir)) {
                    Editor_Service_Tools::DelTree($messageDir);
                }

                $messageDir = $paths['data'] . "messages/$messageId/";
                if (is_dir($messageDir)) {
                    Editor_Service_Tools::DelTree($messageDir);
                }
                $this->_helper->FlashMessenger($this->view->translate('Message successfuly deleted'));
            } else {
                $this->_helper->FlashMessenger($this->view->translate('Cannot found this message'));
            }

            // Rédirection en fonction du type du message
            switch ($type) {
                case Editor_Model_Message_Row::SITE_MOBILE :
                    DM_Session::SetEntry(Dm_Session::GLOBAL_REPLACEMENTS_RULES, array('/#siteShortUrl#/' => ''));
                    $this->_redirect($this->view->href('site'));
                    break;
                default :
                    $this->_redirect($this->view->href('message', array('type' => $type)));
                    break;
            }
        }catch (Exception $e) {
            Dm_Log::Trace($e->getMessage());
            $this->_helper->FlashMessenger->addMessage(
                array('error' => $e->getMessage())
            );
            $messageHandler->getAdapter()->rollBack();
        }
    }

    /**
     * Mise en ligne ou hors ligne d'un site
     *
     * messageId - Identifiant du site à supprimer
     *
     * @return void
     */
    public function broadcastAction()
    {
        $messageId = $this->getRequest()->getParam('messageId');
        $table = new Editor_Model_Site_Table();
        $site = $table->find($messageId)->current();
        $site->online = ($site->online == 1 ? 0 : 1);
        $site->save();
        $this->_redirect($this->view->href('site'));
    }

    /**
     * Generation/serialisation d'un site
     *
     * messageId - Identifiant du site à supprimer
     *
     * @return void
     */
    public function generateAction()
    {
        $messageId = $this->getRequest()->getParam('messageId');
        $table = new Editor_Model_Site_Table();
        $site = $table->find($messageId)->current();
        $engine = new Editor_Model_Engine();
        $engine->processSite($site);
        $this->_redirect($this->view->href('site'));
    }

    /**
     * Duplication d'un site
     *
     * messageId - Identifiant du site à supprimer
     *
     * @return void
     */
    public function duplicateAction()
    {
        $messageId = $this->getRequest()->getParam('messageId');
        if (!is_numeric($messageId)) {
            throw new Exception('Cannot add page without message identifier');
        }
        $table = new Editor_Model_Message_Table();
        $originalMessage = $table->find($messageId)->current();
        $originalMessage->duplicate();

        // Rédirection en fonction du type du message
        switch ($originalMessage->type) {
            case Editor_Model_Message_Row::SITE_MOBILE :
                $this->_redirect($this->view->href('site'));
                break;
            default :
                $this->_redirect($this->view->href('message', array('type' => $originalMessage->type)));
                break;
        }
    }

    /**
     * This action allow a user to import an zip archive from an URL
     *
     * @return void
     */
    public function importAction()
    {
        $importPath = Dm_Config::GetPath('tmp') . 'import';

        //On verifit si le repertoire d'import existe
        if (!file_exists($importPath)) {
            mkdir($importPath);
        }
        //On cree un repertoire pour l'import en court
        $importPath .='/' . microtime(true) . '/';
        if (!file_exists($importPath)) {
            mkdir($importPath);
        }

        $fileName = time() . "." . substr($_FILES['myfile']['type'], strpos($_FILES['myfile']['type'], '/') + 1);
        if (move_uploaded_file($_FILES['myfile']['tmp_name'], $importPath . $fileName)) {
            $result = Editor_Service_Message::Import($importPath . $fileName);
            $this->_helper->FlashMessenger->addMessage(
                array('info' => $_FILES['name'] . ' imported (' . $result->messageId . ')')
            );
        } else {
            $this->_helper->FlashMessenger->addMessage(
                array('error' => 'Cannot found archive to import')
            );
        }

        $target = $this->_getParam('target', 'message-list');
        $this->_redirect($target);
    }

    /**
     * This action allow a user to import an zip archive which contain several templates
     *
     * @return void
     */
    public function massImportAction()
    {
        $importPath = Dm_Config::GetPath('tmp') . 'import';

        //On verifit si le repertoire d'import existe
        if (!file_exists($importPath)) {
            mkdir($importPath);
        }
        //On cree un repertoire pour l'import en court
        $importPath .='/' . microtime(true) . '/';
        if (!file_exists($importPath)) {
            mkdir($importPath);
        }

        $fileName = time() . "." . substr($_FILES['myfile']['type'], strpos($_FILES['myfile']['type'], '/') + 1);
        if (move_uploaded_file($_FILES['myfile']['tmp_name'], $importPath . $fileName)) {
            $result = Editor_Service_Message::MultiImport($importPath, $fileName);
            foreach ($result as $key => $value) {
                $this->_helper->FlashMessenger->addMessage(array($key => $value));
            }
        } else {
            $this->_helper->FlashMessenger->addMessage(
                array('error' => 'Cannot found archive to import')
            );
        }
        Editor_Service_Tools::DelTree($importPath);

        $target = $this->_getParam('target', 'message-list');
        $this->_redirect($target);
    }

    /**
     * Export d'un site
     *
     * messageId - Identifiant du site à supprimer
     *
     * @return void
     */
    public function exportAction()
    {
        $messageId = $this->getRequest()->getParam('messageId');
        if (!is_numeric($messageId)) {
            throw new Exception('Cannot export a message without message identifier');
        }
        $table = new Editor_Model_Message_Table();
        $originalMessage = $table->find($messageId)->current();
        /* @var $originalMessage Editor_Model_Message_Row */
        $zipFilePath = Editor_Service_Message::ExportMessage($originalMessage);
        header('Content-Type: application/octet-stream');
        header("Content-type: application/force-download");
        header("Content-Transfer-Encoding: Binary");
        if ($originalMessage->template == 1) {
            header('Content-Disposition: attachment; filename=' .
                str_replace(' ', '-', ucwords($originalMessage->type . ' ' . $originalMessage->name)) . '.zip');
        } else {
            header('Content-Disposition: attachment; filename=export_message_' . $messageId . '.zip');
        }
        readfile($zipFilePath);
        $this->view->layout()->disableLayout();
        $this->_helper->viewRenderer->setNoRender(true);
    }

    /**
     * ???
     *
     * @return ???
     */
    public function smsCharsAction()
    {
        $this->view->layout()->disableLayout();
    }

}
