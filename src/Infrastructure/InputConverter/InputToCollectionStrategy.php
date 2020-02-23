<?php
declare(strict_types=1);

namespace App\Infrastructure\InputConverter;

use App\Domain\Collection\PublicationCollectionStrategy;
use App\Domain\Collection\PublicationStrategyInterface;
use Symfony\Component\Console\Input\InputInterface;
use function array_walk;
use function explode;

class InputToCollectionStrategy implements PublicationStrategyInterface
{
    public static function convertInputToCollectionStrategy(
        InputInterface $input
    ): PublicationCollectionStrategy {
        $strategy = new PublicationCollectionStrategy();

        $strategy->forMemberHavingScreenName(self::screenName($input));

        self::listRestriction($input, $strategy);
        self::listCollectionRestriction($input, $strategy);
        self::collectionSchedule($input, $strategy);
        self::aggregatePriority($input, $strategy);
        self::queryRestriction($input, $strategy);
        self::memberRestriction($input, $strategy);
        self::ignoreWhispers($input, $strategy);
        self::includeOwner($input, $strategy);

        return $strategy;
    }

    /**
     * @param InputInterface $input
     *
     * @return string
     */
    protected static function screenName(
        InputInterface $input
    ): string {
        return $input->getOption(self::RULE_SCREEN_NAME);
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function listRestriction(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ): void {
        if (
            $input->hasOption(self::RULE_LIST)
            && $input->getOption(self::RULE_LIST) !== null
        ) {
            $strategy->willApplyListRestrictionToAList($input->getOption(self::RULE_LIST));
        }
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function listCollectionRestriction(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ): void {
        if ($input->hasOption(self::RULE_LISTS) && $input->getOption(self::RULE_LISTS) !== null) {
            $listCollectionRestriction = explode(
                ',',
                $input->getOption(self::RULE_LISTS)
            );

            $restiction       = (object) [];
            $restiction->list = [];
            array_walk(
                $listCollectionRestriction,
                function ($list) use ($restiction) {
                    $restiction->list[$list] = $list;
                }
            );
            $listCollectionRestriction = $restiction->list;
            $strategy->willApplyRestrictionToAListCollection($listCollectionRestriction);
        }
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function collectionSchedule(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ): void {
        if (
            $input->hasOption(self::RULE_BEFORE)
            && $input->getOption(self::RULE_BEFORE) !== null
        ) {
            $strategy->willCollectPublicationsPrecedingThoseAlreadyCollected(
                $input->getOption(self::RULE_BEFORE)
            );
        }
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function aggregatePriority(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ): void {
        if (
            $input->hasOption(self::RULE_PRIORITY_TO_AGGREGATES)
            && $input->getOption(self::RULE_PRIORITY_TO_AGGREGATES)
        ) {
            $strategy->willPrioritizeAggregates($input->getOption(self::RULE_PRIORITY_TO_AGGREGATES));
        }
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function queryRestriction(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ): void {
        if (
            $input->hasOption(self::RULE_QUERY_RESTRICTION)
            && $input->getOption(self::RULE_QUERY_RESTRICTION)
        ) {
            $strategy->willApplyQueryRestriction($input->getOption(self::RULE_QUERY_RESTRICTION));
        }
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function memberRestriction(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ): void {
        if (
            $input->hasOption(self::RULE_MEMBER_RESTRICTION)
            && $input->getOption(
                self::RULE_MEMBER_RESTRICTION
            )
        ) {
            $strategy->willApplyRestrictionToAMember($input->getOption(self::RULE_MEMBER_RESTRICTION));
        }
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function ignoreWhispers(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ): void {
        if (
            $input->hasOption(self::RULE_IGNORE_WHISPERS)
            && $input->getOption(
                self::RULE_IGNORE_WHISPERS
            )
        ) {
            $strategy->willIgnoreWhispers($input->getOption(self::RULE_IGNORE_WHISPERS));
        }
    }

    /**
     * @param InputInterface                $input
     * @param PublicationCollectionStrategy $strategy
     */
    private static function includeOwner(
        InputInterface $input,
        PublicationCollectionStrategy $strategy
    ) {
        if (
            $input->hasOption(self::RULE_INCLUDE_OWNER)
            && $input->getOption(self::RULE_INCLUDE_OWNER)
        ) {
            $strategy->willIncludeOwner($input->getOption(self::RULE_INCLUDE_OWNER));
        }
    }
}