<?php
namespace Montapacking\MontaCheckout\Controller\Adminhtml\Download;

class GetFile extends AbstractLog
{
    protected function getFilePathWithFile($fileName)
    {
        return 'var/log/' . $fileName;
    }
}
