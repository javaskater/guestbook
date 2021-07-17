<?php
namespace App\Tests\Controller;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use App\Repository\CommentRepository;
use Doctrine\ORM\EntityManagerInterface;
//use Symfony\Component\Panther\PantherTestCase;

class ConferenceControllerTest extends WebTestCase 
{
    public function testIndex(){
        $client = static::createClient([],['HTTP_HOST' => 'localhost:8000']);
        //$client = static::createPantherClient(['external_base_uri' => $_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL']]);
        //$client->createChromeClient('/snap/bin/chromium');
        $client->request('GET','/');

        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Give your Feedback');
    }


    public function testCommentSubmission(){
        $client = static::createClient([],['HTTP_HOST' => 'localhost:8000']);
        //$client = static::createPantherClient(['external_base_uri' => $_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL']]);
        //$client->createChromeClient('/snap/bin/chromium');
        $client->request('GET','/conference/amsterdam-2019');
        $client->submitForm('Submit',[
            'comment_form[author]' => 'Fabien',
            'comment_form[text]' => 'Some feedback from an automated test',
            'comment_form[email]' => $email = 'me@automat.ed',
            'comment_form[photo]' => dirname(__DIR__,2).'/public/images/under-construction.gif',
        ]);
        $this->assertResponseRedirects();
        //simulate the comment validation
        $comment = self::$container->get(CommentRepository::class)->findOneByEmail($email);
        $comment->setState('published');
        self::$container->get(EntityManagerInterface::class)->flush();
        $client->followRedirect();
        $this->assertSelectorExists('div:contains("There are 2 comments")');
    }

    public function testConferencePage(){
        $client = static::createClient([],['HTTP_HOST' => 'localhost:8000']);
        //$client = static::createPantherClient(['external_base_uri' => $_SERVER['SYMFONY_PROJECT_DEFAULT_ROUTE_URL']]);
        $crawler = $client->request('GET','/');

        $this->assertCount(2, $crawler->filter('h4'));
        
        $client->clickLink('View');
        $this->assertPageTitleContains('Amsterdam');
        $this->assertResponseIsSuccessful();
        $this->assertSelectorTextContains('h2', 'Amsterdam 2019');
        $this->assertSelectorExists('div:contains("There are 1 comments")');
    }
}