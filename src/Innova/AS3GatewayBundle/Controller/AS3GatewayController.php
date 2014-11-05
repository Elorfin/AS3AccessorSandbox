<?php

namespace Innova\AS3GatewayBundle\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\Controller;

use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Route;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\Template;
use Symfony\Component\HttpFoundation\Response;

use AmazonS3;

/**
 * Class AS3GatewayController
 */
class AS3GatewayController extends Controller
{
    /**
     * List AS3 Files
     * @Route(
     *      "",
     *      name = "innova_as3_gateway_list"
     * )
     * @Template()
     */
    public function listAction()
    {
        $as3 = $this->get('knp_gaufrette.filesystem_map')->get('amazon');

        // Create Upload form
        $form = $this->createFormBuilder()
            ->add('upload_file', 'file')
            ->add('save', 'submit')
            ->getForm();

        if ($this->getRequest()->isMethod('POST')) {
            // Process form if needed
            $form->handleRequest($this->getRequest());
            if ($form->isValid()) {
                $data = $form->getData();
                $uploadedFile = $data['upload_file'];

                $filename = $uploadedFile->getClientOriginalName();

                $adapter = $as3->getAdapter();
                $adapter->setAcl(AmazonS3::ACL_PRIVATE);
                $adapter->setMetadata($filename, array('contentType' => $uploadedFile->getClientMimeType()));
                $adapter->write($filename, file_get_contents($uploadedFile->getPathname()));
            }
        }

        // List all files in AS3
        $list = $as3->keys();

        return array (
            'files' => $list,
            'upload_form' => $form->createView(),
        );
    }

    /**
     * Show a specific file from AS3
     * @Route(
     *      "/show/{name}",
     *      name = "innova_as3_gateway_show",
     *      requirements = {"name" = ".+"}
     * )
     * @Method("GET")
     */
    public function showAction($name)
    {
        $as3 = $this->get('knp_gaufrette.filesystem_map')->get('amazon');
        $file = $as3->get($name);

        $response = new Response();

        $response->headers->set('Content-Type', $file->getMtime());
        $response->headers->set('Content-Disposition', 'inline;filename="'.$name);
        $response->sendHeaders();
        $response->setContent($file->getContent());

        return $response;
    }
}