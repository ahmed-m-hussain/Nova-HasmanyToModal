<?php

namespace AhmedHussain\HasmanyToModal;

use Laravel\Nova\Fields\Collapsable;
use Laravel\Nova\Fields\Field;

use Illuminate\Support\Str;
use Illuminate\Http\Request;
use Laravel\Nova\Contracts\ListableField;
use Laravel\Nova\Fields\ResourceRelationshipGuesser;
use Laravel\Nova\Fields\SupportsDependentFields;
use Laravel\Nova\Panel;

use Laravel\Nova\Contracts\RelatableField;
use Laravel\Nova\Exceptions\HelperNotSupported;
use Laravel\Nova\Exceptions\NovaException;

/**
 * @method static static make(mixed $name, string|null $attribute = null, string|null $resource = null)
 */
class HasmanyToModal extends Field implements RelatableField
{
    use SupportsDependentFields;
    use Collapsable;

    /**
     * The field's component.
     *
     * @var string
     */
    public $component = 'hasmany-to-modal';

    /**
     * Indicates if the element should be shown on the edit pages.
     *
     * @var bool
     */
    public $showOnCreation = false;
    public $showOnUpdate = false;
    /**
     * The class name of the related resource.
     *
     * @var string
     */
    public $resourceClass;

    /**
     * The URI key of the related resource.
     *
     * @var string
     */
    public $resourceName;

    /**
     * The name of the Eloquent "has many" relationship.
     *
     * @var string
     */
    public $hasManyRelationship;

    /**
     * The displayable singular label of the relation.
     *
     * @var string
     */
    public $singularLabel;

    /**
     * @var string
     */
    public $modalSize = "7xl";


    /**
     * Create a new field.
     *
     * @param string $name
     * @param string|null $attribute
     * @param class-string<\Laravel\Nova\Resource>|null $resource
     * @return void
     */
    public function __construct($name, $attribute = null, $resource = null)
    {
        parent::__construct($name, $attribute);

        $resource = $resource ?? ResourceRelationshipGuesser::guessResource($name);

        $this->resourceClass = $resource;
        $this->resourceName = $resource::uriKey();
        $this->hasManyRelationship = $this->attribute = $attribute ?? ResourceRelationshipGuesser::guessRelation($name);
    }


    public static function authorizedToCreate(Request $request)
    {
        return false;
    }


    /**
     * Determine if the field should be displayed for the given request.
     *
     * @param \Illuminate\Http\Request $request
     * @return bool
     */
    public function authorize(Request $request)
    {
        return call_user_func(
                [$this->resourceClass, 'authorizedToViewAny'], $request
            ) && parent::authorize($request);
    }


    public function singularLabel($singularLabel)
    {
        $this->singularLabel = $singularLabel;

        return $this;
    }

    public function perPage($perPage = 25)
    {
        $this->resourceClass::$perPageViaRelationship = $perPage;

        return $this;
    }

    /**
     * Get additional meta information to merge with the field payload.
     *
     * @return array
     */
    public function meta()
    {
        return array_merge([
            'resourceName' => $this->resourceName,
            'hasManyRelationship' => $this->hasManyRelationship,
            'singularLabel' => $this->singularLabel ?? Str::singular($this->name),
            'perPage' => $this->resourceClass::$perPageViaRelationship,
            'page' => 1,
        ], $this->meta);
    }

    /**
     * Resolve the field's value for display.
     *
     * @param mixed $resource
     * @param string|null $attribute
     * @return void
     */
    public function resolveForDisplay($resource, $attribute = null)
    {
        $attribute = $attribute ?? $this->attribute;
        $this->value = [
            "resourceId" => $resource->id,
            "totalRelationship" => $resource->$attribute()->count(),
            'title' => $this->singularLabel ?? Str::singular($this->name),
        ];
    }


    /**
     * Get the relationship name.
     *
     * @return string
     */
    public function relationshipName()
    {
        return $this->hasManyRelationship;
    }

    /**
     * Get the relationship type.
     *
     * @return string
     */
    public function relationshipType()
    {
        return 'hasMany';
    }



    /**
     * Make current field behaves as panel.
     *
     * @return \Laravel\Nova\Panel
     */
    public function asPanel()
    {
        return Panel::make($this->name, [$this])
            ->withMeta([
                'prefixComponent' => true,
            ])->withComponent('relationship-panel');
    }

    /**
     * Prepare the field for JSON serialization.
     *
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return array_merge([
            'collapsable' => $this->collapsable,
            'collapsedByDefault' => $this->collapsedByDefault,
            'hasManyRelationship' => $this->hasManyRelationship,
            'relatable' => true,
            'modalSize' => $this->modalSize,
            'perPage' => $this->resourceClass::$perPageViaRelationship,
            'resourceName' => $this->resourceName,
            'singularLabel' => $this->singularLabel ?? $this->resourceClass::singularLabel(),
        ], parent::jsonSerialize());
    }

    public function modalSize(string $modalSize)
    {
        $this->modalSize = $modalSize;

        return $this;
    }


}
