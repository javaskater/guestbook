<?php
namespace App;

use App\Entity\Comment;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class SpamChecker 
{
    private $client;
    private $endpoint;

    public function __construct(HttpClientInterface $client, string $askimetKey){
        $this->client = $client;
        $this->endpoint = sprintf('https://%s.rest.askimet.com/1.1/comment-check', $askimetKey);
    }

    public function getSpamScore(Comment $comment, array $context):int{
        $response = $this->client->request('POST', $this->endpoint, [
            'body' => array_merge(
                $context, [
                    'blog' => 'https://127.0.0.1:8000/',
                    'comment-type' => 'comment',
                    'comment_author' => $comment->getAuthor(),
                    'comment_author_email' => $comment->getEmail(),
                    'comment_content' => $comment->getText(),
                    'comment_date_gmt' => $comment->getCreatedAt()->format('c'),
                    'blog_lang' => 'en',
                    'blog_charset' => 'UTF-8',
                    'is_test' => true,
                ])
        ]);
        $headers = $response->getHeaders();
        if ('discard' === ($headers['x-askimet-pro-tip'][0] ?? '')){
            return 2;
        }
        

        $content = $response->getContent();
        
        if (isset($headers['x-askimet-debug-help'][0])){
            throw new \RuntimeException(sprintf('Unable to check for spam %s (%s)', $content, $headers['x-askimet-debug-help'][0]));
        }
        
        return 'true' == $content ? 1: 0;

    }
}