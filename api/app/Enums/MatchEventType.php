<?php

namespace App\Enums;

enum MatchEventType: string
{
    case Kickoff           = 'kickoff';
    case PossessionStart   = 'possession_start';
    case PassCompleted     = 'pass_completed';
    case PassFailed        = 'pass_failed';
    case DribbleSuccess    = 'dribble_success';
    case DribbleFailed     = 'dribble_failed';
    case FoulCommitted     = 'foul_committed';
    case ShotAttempt       = 'shot_attempt';
    case ShotOffTarget     = 'shot_off_target';
    case ShotSaved         = 'shot_saved';
    case ShotBlocked       = 'shot_blocked';
    case Goal              = 'goal';
    case CornerWon         = 'corner_won';
    case CornerTaken       = 'corner_taken';
    case Interception      = 'interception';
    case TackleWon         = 'tackle_won';
    case FreeKickAwarded   = 'free_kick_awarded';
    case HalfTime          = 'half_time';
    case FullTime          = 'full_time';

    /** Events written to the match_events timeline. Quiet events are skipped. */
    public function isVisible(): bool
    {
        return match($this) {
            self::Kickoff, self::HalfTime, self::FullTime,
            self::Goal, self::ShotAttempt, self::ShotOffTarget,
            self::ShotSaved, self::ShotBlocked,
            self::FoulCommitted, self::FreeKickAwarded,
            self::CornerWon, self::CornerTaken,
            self::Interception, self::TackleWon => true,
            default                             => false,
        };
    }

    /** Events that hand possession to the other team. */
    public function causesPossessionChange(): bool
    {
        return match($this) {
            self::PassFailed, self::DribbleFailed,
            self::Interception, self::TackleWon => true,
            default                             => false,
        };
    }
}
