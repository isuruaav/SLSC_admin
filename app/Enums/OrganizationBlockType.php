<?php

namespace App\Enums;

enum OrganizationBlockType: string
{
    case Paragraph = 'paragraph';
    case Heading = 'heading';
    case RichText = 'rich_text';
    case Image = 'image';
    case Gallery = 'gallery';
    case Table = 'table';
    case List = 'list';
    case Contact = 'contact';
    case Timeline = 'timeline';
    case Document = 'document';
    case TwoColumn = 'two_column';

    public function label(): string
    {
        return match ($this) {
            self::Paragraph => 'Paragraph',
            self::Heading => 'Heading',
            self::RichText => 'Rich Text',
            self::Image => 'Single Image',
            self::Gallery => 'Image Gallery',
            self::Table => 'Data Table',
            self::List => 'List (Bullet / Numbered)',
            self::Contact => 'Contact Information',
            self::Timeline => 'Timeline / Commanding Officers',
            self::Document => 'Document / Downloads',
            self::TwoColumn => 'Two Columns',
        };
    }

    public function icon(): string
    {
        return match ($this) {
            self::Paragraph => 'fa-solid fa-paragraph',
            self::Heading => 'fa-solid fa-heading',
            self::RichText => 'fa-solid fa-align-left',
            self::Image => 'fa-regular fa-image',
            self::Gallery => 'fa-solid fa-images',
            self::Table => 'fa-solid fa-table',
            self::List => 'fa-solid fa-list-ul',
            self::Contact => 'fa-solid fa-address-card',
            self::Timeline => 'fa-solid fa-timeline',
            self::Document => 'fa-solid fa-file-pdf',
            self::TwoColumn => 'fa-solid fa-table-columns',
        };
    }
}

