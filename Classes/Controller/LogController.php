<?php
namespace VerteXVaaR\Logs\Controller;

use TYPO3\CMS\Extbase\Mvc\Controller\ActionController;
use VerteXVaaR\Logs\Log\Reader\ConjunctionReader;
use VerteXVaaR\Logs\Log\Reader\ReaderInterface;

/**
 * Class LogController
 */
class LogController extends ActionController
{
    /**
     * @var ReaderInterface
     */
    protected $reader = null;

    /**
     *
     */
    public function initializeObject()
    {
        $this->reader = ConjunctionReader::fromConfiguration();
    }

    /**
     *
     */
    public function filterAction()
    {
        $this->view->assign('logs', $this->reader->findAll());
    }
}
