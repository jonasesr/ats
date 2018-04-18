<?php
namespace PAGEmachine\Ats\Controller\Application;

use PAGEmachine\Ats\Domain\Model\Application;
use PAGEmachine\Ats\Service\ExtconfService;

/*
 * This file is part of the PAGEmachine ATS project.
 */

/**
 * SubmitController (Last Step)
 */
class SubmitController extends AbstractApplicationController
{
    /**
     * applicationRepository
     *
     * @var \PAGEmachine\Ats\Domain\Repository\ApplicationRepository
     * @inject
     */
    protected $repository = null;

    /**
     * @var \PAGEmachine\Ats\Message\MessageFactory
     * @inject
     */
    protected $messageFactory;

    /**
     * @param  Application $application
     * @ignorevalidation $application
     * @return void
     */
    public function showSummaryAction(Application $application)
    {
        $this->view->assign("application", $application);
    }

    /**
     * @param  Application $application
     * @return void
     */
    public function submitAction(Application $application)
    {

        $application->submit();

        $this->repository->updateAndLog(
            $application,
            'new'
        );

        if( ExtconfService::getInstance()->getSendAutoAcknowledge() ){
            $this->forward("sendAutoAcknowledgement", null, null, ['application' => $application]);
        }else{
            $this->redirect("submited", null, null, ['application' => $application]);
        }
    }

    /**
     * @param  Application $application
     * @return void
     */
    public function submitedAction(Application $application)
    {

    }

    /**
     * @param  Application $application
     * @return void
     */
    public function sendAutoAcknowledgementAction(Application $application){
        $templateUid = ExtconfService::getInstance()->getAutoAcknowledgeTemplate();

        $message = $this->messageFactory->createMessage("acknowledge", $application);

        if(array_key_exists( $templateUid, $message->getTextTemplateDropdownOptions() )){
            $message->setTextTemplate($templateUid);
            $message->applyTextTemplate();

            $this->repository->updateAndLog(
                $message->getApplication(),
                'autoAcknowledge',
                [
                    'subject' => $message->getRenderedSubject(),
                    'sendType' => $message->getSendType(),
                    'cc' => $message->getCc(),
                    'bcc' => $message->getBcc(),
                    'message' => $message->getRenderedBody(),
                ]
            );

            $message->send();
        }

        $this->redirect("submited", null, null, ['application' => $application]);
    }
}
