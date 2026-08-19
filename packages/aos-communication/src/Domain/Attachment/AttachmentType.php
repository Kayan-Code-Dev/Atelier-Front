<?php

declare(strict_types=1);

namespace DressnMore\Aos\Communication\Domain\Attachment;

enum AttachmentType: string
{
    case Image = 'image';
    case Video = 'video';
    case Audio = 'audio';
    case VoiceNote = 'voice_note';
    case Pdf = 'pdf';
    case Document = 'document';
    case Spreadsheet = 'spreadsheet';
    case ContactCard = 'contact_card';
    case Location = 'location';
    case Sticker = 'sticker';
    case FutureMedia = 'future_media';
}
