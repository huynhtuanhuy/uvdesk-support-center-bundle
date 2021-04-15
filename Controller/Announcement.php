<?php

namespace Webkul\UVDesk\SupportCenterBundle\Controller;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Webkul\UVDesk\SupportCenterBundle\Entity\Website;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Webkul\UVDesk\CoreFrameworkBundle\Services\UserService;
use Webkul\UVDesk\CoreFrameworkBundle\FileSystem\FileSystem;
use Symfony\Component\Translation\TranslatorInterface;

Class Announcement extends AbstractController
{
    private $translator;
    private $userService;

    public function __construct(TranslatorInterface $translator, UserService $userService)
    {
        $this->translator = $translator;
        $this->userService = $userService;
    }

    public function listAnnouncement(Request $request)    
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        return $this->render('@UVDeskSupportCenter/Staff/Announcement/listAnnouncement.html.twig');
    }

    public function listAnnouncementXHR(Request $request)    
    {
        $json = array();
        $repository = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Announcement');
        $json =  $repository->getAllAnnouncements($request->query, $this->container);
        
        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }

    public function updateAnnouncement(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }
        
        $em = $this->getDoctrine()->getManager();
        
        if($request->attributes->get('id')){
            $announcement = $this->getDoctrine()->getRepository('UVDeskSupportCenterBundle:Announcement')
                        ->findOneBy([
                                'id' => $request->attributes->get('id')
                            ]);
            $announcement->setCreatedAt(new \DateTime('now'));          
            if(!$announcement)
                $this->noResultFound();
        } else {
            $announcement = new Announcement;
            $announcement->setCreatedAt(new \DateTime('now'));
        }
            
        if($request->getMethod() == "POST") {
            $request = $request->request->get('announcement_form');
            $group = $em->getRepository('UVDeskCoreFrameworkBundle:SupportGroup')->find($request['group']);

            $announcement->setTitle($request['title']);
            $announcement->setPromoText($request['promotext']);
            $announcement->setPromotag($request['promotag']);
            $announcement->setTagColor($request['tagColor']);
            $announcement->setLinkText($request['linkText']);
            $announcement->setLinkURL($request['linkURL']);
            $announcement->setIsActive($request['status']);
            $announcement->setGroup($group);
            $em->persist($announcement);
            $em->flush();

            $this->addFlash('success', 'Success! Announcement data saved successfully.');
            return $this->redirect($this->generateUrl('helpdesk_member_knowledgebase_marketing_announcement'));
            
        }

        return $this->render('@UVDeskSupportCenter/Staff/Announcement/announcementForm.html.twig', [
                'announcement' => $announcement,
                'errors' => ''
        ]);
    }

    public function removeAnnouncementXHR(Request $request)
    {
        if (!$this->userService->isAccessAuthorized('ROLE_AGENT_MANAGE_KNOWLEDGEBASE')) {
            return $this->redirect($this->generateUrl('helpdesk_member_dashboard'));
        }

        $entityManager = $this->getDoctrine()->getManager();
        $knowledgebaseAnnouncementId = $request->attributes->get('id');

        $knowledgebaseAnnouncement = $entityManager->getRepository(Announcement::class)->findOneBy([
            'id' => $knowledgebaseAnnouncementId
        ]);

        if ($knowledgebaseAnnouncement) {
            $entityManager->remove($knowledgebaseAnnouncement);
            $entityManager->flush();

            $json = [
                'alertClass' => 'success',
                'alertMessage' => 'Announcement deleted successfully!',
            ];
            $responseCode = 200;
        } else {
            $json = [
                'alertClass' => 'warning',
                'alertMessage' => 'Announcement not found!',
            ];
        }

        $response = new Response(json_encode($json));
        $response->headers->set('Content-Type', 'application/json');
        return $response;
    }
}
