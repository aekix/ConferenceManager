<?php

namespace App\Controller;

use App\Entity\Conference;
use App\Entity\Task;
use App\Entity\Vote;
use App\Form\ConferenceEditType;
use App\Form\CreateConferenceType;
use App\Form\TaskType;
use App\Form\ValidationType;
use App\Form\VoteConferenceType;
use App\Repository\ConferenceRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Doctrine\ORM\Query;

class ConferenceController extends AbstractController
{
    /**
     * @Route("/", name="home")
     */
    public function index()
    {
        if ($this->isGranted('IS_AUTHENTICATED_FULLY')) {
            return $this->redirectToRoute('conferences');
        } else {
            return $this->redirectToRoute('login');
        }
    }

    /**
     * @Route("/conferences", name="conferences")
     */
    public function getConferences(ConferenceRepository $conferenceRepository, Request $request)
    {
        $task = new Task();
        $exist = 0;
        $conferences = $conferenceRepository->findAll();
        $votesUser = $this->getUser()->getVotes();
        for ($i = 0; $i < count($conferences); $i++) {
            for ($j = 0; $j < count($votesUser); $j++) {
                if ($conferences[$i] == $votesUser[$j]->getConference()) {
                    $task->getVotes()->add($votesUser[$j]);
                    $exist = 1;
                }
            }
            if ($exist == 0) {
                $vote = new Vote();
                $vote->setConference($conferences[$i]);
                $vote->setUser($this->getUser());
                $task->getVotes()->add($vote);
            }
            $exist = 0;
        }
        $forms = $this->createForm(TaskType::class, $task)->handleRequest($request);

        if ($forms->isSubmitted() && $forms->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $votes = $task->getVotes();
            for ($i = 0; $i < count($conferences); $i++) {
                if ($votes[$i]->getNote() > 0) {
                    $entityManager->persist($votes[$i]);
                }
            }
            $entityManager->flush();
        }

        return $this->render('conference/conferences.html.twig', [
            'forms' => $forms->createView(),
            'conferences' => $conferences,
            'size' => count($conferences)
        ]);
    }

    /**
     * @Route("/conferencesVoted/{id}", name="conferencesVoted")
     */
    public function getConferencesVoted(ConferenceRepository $conferenceRepository, Request $request)
    {
        $task = new Task();
        $votesUser = $this->getUser()->getVotes();
        $conferences = $conferenceRepository->findAll();
        $myConfs = array();
        for ($i = 0; $i < count($conferences); $i++) {
            for ($j = 0; $j < count($votesUser); $j++) {
                if ($conferences[$i] == $votesUser[$j]->getConference()) {
                    $myConfs[] = $conferences[$i];
                    $task->getVotes()->add($votesUser[$j]);
                }
            }
            //           $conferences = $votesUser[$i]->getConference();
        }
        $forms = $this->createForm(TaskType::class, $task)->handleRequest($request);

        if ($forms->isSubmitted() && $forms->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $votes = $task->getVotes();
            for ($i = 0; $i < count($votesUser); $i++) {
                if ($votes[$i]->getNote() > 0) {
                    $entityManager->persist($votes[$i]);
                }
            }
            $entityManager->flush();
        }

        return $this->render('conference/conferences.html.twig', [
            'forms' => $forms->createView(),
            'conferences' => $myConfs,
            'size' => count($votesUser)

        ]);
    }

    /**
     * @Route("/conferencesNotVoted/{id}", name="conferencesNotVoted")
     */
    public function getConferencesNotVoted(ConferenceRepository $conferenceRepository, Request $request)
    {
        $task = new Task();
        $votesUser = $this->getUser()->getVotes();
        $conferences = $conferenceRepository->findAll();
        $myConfs = array();
        $exist = 0;
        for ($i = 0; $i < count($conferences); $i++) {
            for ($j = 0; $j < count($votesUser); $j++) {
                if ($conferences[$i] == $votesUser[$j]->getConference()) {
                    $exist = 1;
                }
            }
            if ($exist == 0) {
                $myConfs[] = $conferences[$i];
                $vote = new Vote();
                $vote->setConference($conferences[$i]);
                $vote->setUser($this->getUser());
                $task->getVotes()->add($vote);
            }
            $exist = 0;
            //           $conferences = $votesUser[$i]->getConference();
        }
        $forms = $this->createForm(TaskType::class, $task)->handleRequest($request);

        if ($forms->isSubmitted() && $forms->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $votes = $task->getVotes();
            for ($i = 0; $i < count($task->getVotes()); $i++) {
                if ($votes[$i]->getNote() > 0) {
                    $entityManager->persist($votes[$i]);
                }
            }
            $entityManager->flush();
            return $this->redirectToRoute('conferencesNotVoted', ['id' => $this->getUser()->getId()]);
        }

        return $this->render('conference/conferences.html.twig', [
            'forms' => $forms->createView(),
            'conferences' => $myConfs,
            'size' => count($conferences)

        ]);
    }

    /**
     * @Route("/createConference", name="createConference")
     */
    public function createConference(Request $request)
    {
        $conf = new Conference();
        $conf->setUser($this->getUser());
        $form = $this->createForm(CreateConferenceType::class, $conf);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager = $this->getDoctrine()->getManager();
            $entityManager->persist($conf);
            $entityManager->flush();
            $this->redirectToRoute('gererConferences');
        }
        return $this->render('conference/createConference.html.twig', ['form' => $form->createView()]);
    }

    /**
     * @Route("/conferences/gererConferences", name="gererConferences")
     */
    public function gererConferences(ConferenceRepository $conferenceRepository, Request $request)
    {
        $conferences = $conferenceRepository->findAll();
        return $this->render('admin/gererConferences.html.twig', [
            'conferences' => $conferences,
        ]);
    }

    /**
     * @Route("/conferences/gererConferences/edit/{id}", name="editConference")
     *
     */
    public function editConference(ConferenceRepository $conferenceRepository, Request $request, EntityManagerInterface $entityManager)
    {
        $conference = $conferenceRepository->find($request->get('id'));
        $form = $this->createForm(ConferenceEditType::class, $conference);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($conference);
            $entityManager->flush();
        }
        $repository = $entityManager->getRepository(Conference::class)->findAll();
        return $this->render('admin/editConference.html.twig', [
            'form' => $form->createView(),
            'conferences' => $repository,
        ]);
    }

    /**
     * @Route("/conferences/gererConferences/remove/{id}", name="removeConference")
     */
    public function removeConference(ConferenceRepository $conferenceRepository, EntityManagerInterface $entityManager, Request $request)
    {

        $conference = $conferenceRepository->find($request->get('id'));
        $votes = $conference->getVotes();
        foreach ($votes as $vote) {
            $entityManager->remove($vote);
            $entityManager->flush();
        }
        $entityManager->remove($conference);
        $entityManager->flush();
        $this->addFlash('notice', 'Your changes were saved!');
        return $this->redirectToRoute('gererConferences');
    }

    /**
     * @param Conference|null $conference
     * @return float
     */
    public function getAverage(?Conference $conference): float {
        $votes = $conference->getVotes();
        $sum = 0;
        foreach ($votes as $vote) {
            $sum += $vote->getNote();
        }
        $sum /= count($votes);
        return $sum;
    }
    /**
     * @Route("/conferences/top10", name="top10")
     */
    public function getTop(ConferenceRepository $conferenceRepository, Request $request)
    {
        $notes = array();
        $conferences = $conferenceRepository->findAll();
        foreach ($conferences as $conference){
            $notes[] = $this->getAverage($conference);
        }
        array_multisort($notes, SORT_DESC,SORT_NUMERIC,$conferences, SORT_DESC);
        return $this->render('admin/top10.html.twig', [
            'conferences' => $conferences,
            'notes' => $notes,
        ]);
    }
}
