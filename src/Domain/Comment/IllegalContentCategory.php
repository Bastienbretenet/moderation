<?php

declare(strict_types=1);

namespace SudOuest\Comment\Domain\Comment;

enum IllegalContentCategory: string
{
    case HateSpeech = 'hate_speech';
    case Defamation = 'defamation';
    case Insult = 'insult';
    case Harassment = 'harassment';
    case Threat = 'threat';
    case TerrorismApology = 'terrorism_apology';
    case CrimeAgainstHumanityDenial = 'crime_against_humanity_denial';
    case ChildSexualContent = 'child_sexual_content';
    case PrivacyViolation = 'privacy_violation';
    case Other = 'other';
}
