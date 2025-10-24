<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\AbstractObject;

/**
 * https://w3id.org/fep/044f#QuoteRequest
 */
class QuoteRequest extends AbstractObject
{
    /**
     * The object's unique global identifier
     *
     * @see https://www.w3.org/TR/activitypub/#obj-id
     *
     * @var string
     */
    protected $id;

    /**
     * @var string
     */
    protected $type = 'QuoteRequest';

    /**
     * The context within which the object exists or an activity was
     * performed.
     * The notion of "context" used is intentionally vague.
     * The intended function is to serve as a means of grouping objects
     * and activities that share a common originating context or
     * purpose. An example could be all activities relating to a common
     * project or event.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-context
     *
     * @var string
     *    | ObjectType
     *    | Link
     *    | null
     */
    protected $context;

    /**
     * Describes one or more entities that either performed or are
     * expected to perform the activity.
     * Any single activity can have multiple actors.
     * The actor MAY be specified using an indirect Link.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-actor
     *
     * @var string
     *    | \ActivityPhp\Type\Extended\AbstractActor
     *    | array<Actor>
     *    | array<Link>
     *    | Link
     */
    protected $actor;

    /**
     * The quoted object
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-object-term
     *
     * @var string
     */
    protected $object;

    /**
     * Refers to the quote post
     *
     * @var array
     */
    protected $instrument;

}
