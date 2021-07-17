<?php

namespace App\Controller\Admin;

use App\Entity\Comment;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\FormField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextEditorField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
 

class CommentCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Comment::class;
    }

    
    public function configureFields(string $pageName): iterable
    {
        /*return [
            IdField::new('id'),
            TextField::new('title'),
            TextEditorField::new('description'),
        ];*/
        return [
            FormField::addPanel('Conference'),
            AssociationField::new('conference')
                ->setRequired(true)
                ->setHelp('help text'),
            FormField::addPanel('Comment'),
            TextField::new('author')
                ->setHelp('Your name'),
            TextEditorField::new('text', 'Comment')
                ->setHelp('help text'),
            EmailField::new('email', 'Email Address')
                ->setHelp('Your valid email address'),
            DateTimeField::new('createdAt'),
            TextField::new('photoFilename'),
            TextField::new('state'),
        ];
    }
}
