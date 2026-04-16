<?php

namespace App\Enums;

enum PitchZone: string
{
    case DefensiveThird  = 'defensive_third';
    case MiddleThird     = 'middle_third';
    case AttackingThird  = 'attacking_third';
    case PenaltyArea     = 'penalty_area';

    /** Multiplier applied to shot conversion probability. */
    public function shotConversionModifier(): float
    {
        return match($this) {
            self::PenaltyArea    => 0.26,
            self::AttackingThird => 0.12,
            self::MiddleThird    => 0.04,
            self::DefensiveThird => 0.01,
        };
    }

    /** Advance one zone toward the opponent's goal. */
    public function advance(): self
    {
        return match($this) {
            self::DefensiveThird => self::MiddleThird,
            self::MiddleThird    => self::AttackingThird,
            self::AttackingThird => self::PenaltyArea,
            self::PenaltyArea    => self::PenaltyArea,
        };
    }

    /**
     * Flip perspective after a possession change.
     * The new possessor starts in the mirror zone.
     */
    public function flipForPossessionChange(): self
    {
        return match($this) {
            self::PenaltyArea    => self::DefensiveThird,
            self::AttackingThird => self::MiddleThird,
            self::MiddleThird    => self::MiddleThird,
            self::DefensiveThird => self::AttackingThird,
        };
    }

    public function isAttackingZone(): bool
    {
        return match($this) {
            self::AttackingThird, self::PenaltyArea => true,
            default                                 => false,
        };
    }
}
