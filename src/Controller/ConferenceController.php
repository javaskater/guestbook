<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use App\Repository\ConferenceRepository;
use App\Entity\Comment;
use App\Message\CommentMessage;
use App\Form\CommentFormType;
use App\Entity\Conference;
//use App\SpamChecker;
use Symfony\Component\Messenger\MessageBusInterface;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Twig\Environment; 

class ConferenceController extends AbstractController
{
    private $twig;
    private $entityManager;
    private $bus;

    public function __construct(Environment $twig, EntityManagerInterface $entityManager, MessageBusInterface $bus){
        $this->twig = $twig;
        $this->entityManager = $entityManager;
        $this->bus = $bus;
    }

    /**
     * @Route("/conference_header", name="conference_header")
     */
    public function conferenceHeader(ConferenceRepository $conferenceRepository):Response{
        $response = $this->render('conference/header.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);
        /*$response = new Response($this->twig->render('conference/index.html.twig', []));*/
        //$response->setSharedMaxAge(3600);

        return $response;
    }
    
    /**
     * @Route("/", name="homepage")
     */
    public function index(ConferenceRepository $conferenceRepository):Response{
        $response = new Response($this->twig->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]));
        //$response->setSharedMaxAge(3600);

        return $response;

        /*$greet = "";
        if ($name){
            $greet = sprintf("<h1>Hello %s!</h1>", htmlspecialchars($name));
        }

        return new Response(<<<EOF
<html>
    <body>
    $greet
        <img src="images/under-construction.gif" />
    </body>
</html>
EOF
        ); */
    }

    /**
     * @Route("/conference/{slug}", name="conference")
    */
    public function show(Request $request, Conference $conference, CommentRepository $commentRepository, string $photoDir): Response {
        $comment = new Comment();
        $form = $this->createForm(CommentFormType::class, $comment);
        $form->handleRequest($request);
        if ($form->isSubmitted() && $form->isValid()){
            $comment->setConference($conference);
            if ($photo = $form['photo']->getData()){
                $filename = bin2hex(random_bytes(6)).'.'.$photo->guessExtension();
                try{
                    $photo->move($photoDir, $filename);
                } catch (FileException $e){

                }
                $comment->setPhotoFilename($filename);
            }
            $this->entityManager->persist($comment);
            $this->entityManager->flush();
            $context = [
                'user_ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('user-agent'),
                'referer' => $request->headers->get('referer'),
                'permalink' => $request->getUri(),
            ];
            /*if (2 === $spamChecker->getSpamScore($comment, $context)){
                throw new \RuntimeException('Blatant spam, go away!!!');
            }
            $this->entityManager->flush();*/
            $this->bus->dispatch(new CommentMessage($comment->getId(), $context));
            return $this->redirectToRoute('conference', ['slug'=> $conference->getSlug()]);
        }
        $offset = max(0, $request->query->getInt('offset',0));
        $paginator = $commentRepository->getCommentPaginator($conference, $offset);
        //var_dump(count($paginator));
        return new Response($this->twig->render('conference/show.html.twig', [
            'conference'=>$conference,
            'comments'=>$paginator,
            'previous'=>$offset - CommentRepository::PAGINATOR_PER_PAGE,
            'next'=>min(count($paginator), $offset + CommentRepository::PAGINATOR_PER_PAGE),
            'comment_form'=>$form->createView()
        ]));
    }
}
