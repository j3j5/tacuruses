<?php

declare(strict_types=1);

namespace App\Domain\ActivityPub\Mastodon;

use ActivityPhp\Type\AbstractObject;

/**
 * https://w3id.org/fep/044f#QuoteAuthorization
 */
class QuoteAuthorization extends AbstractObject
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
    protected $type = 'QuoteAuthorization';

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
     * One or more entities to which this object is attributed.
     * The attributed entities might not be Actors. For instance, an
     * object might be attributed to the completion of another activity.
     *
     * @see https://www.w3.org/TR/activitystreams-vocabulary/#dfn-attributedto
     *
     * @var string
     *    | ObjectType
     *    | Link
     *    | array<ObjectType>
     *    | array<Link>
     *    | null
     */
    protected $attributedTo;

    /**
     * It is the quote post under consideration
     *
     * @see https://gotosocial.org/ns#interactingObject
     * @var string
     *    | Link
     */
    protected $interactingObject;

    /**
     * It is the quoted object
     *
     * @see https://gotosocial.org/ns#interactingTarget
     * @var string
     *    | Link
     */
    protected $interactionTarget;

}
