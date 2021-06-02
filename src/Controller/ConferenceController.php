<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
//use App\Repository\ConferenceRepository;
use App\Entity\Comment;
use App\Form\CommentFormType;
use App\Entity\Conference;
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

    public function __construct(Environment $twig, EntityManagerInterface $entityManager){
        $this->twig = $twig;
        $this->entityManager = $entityManager;
    }
    
    /**
     * @Route("/", name="homepage")
     */
    public function index():Response{
        /*return $this->render('conference/index.html.twig', [
            'conferences' => $conferenceRepository->findAll(),
        ]);*/
        return new Response($this->twig->render('conference/index.html.twig', []));

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