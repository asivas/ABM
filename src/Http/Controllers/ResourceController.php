<?php

namespace Asivas\ABM\Http\Controllers;

use Asivas\ABM\Exceptions\ABMException;
use Asivas\ABM\Exceptions\FormFieldValidationException;
use Asivas\ABM\Form\FieldGroup;
use Asivas\ABM\Form\FieldSet;
use Asivas\ABM\Form\FormField;
use Asivas\ABM\Http\ColumnField;
use Asivas\ABM\Http\ColumnFieldType;
use Asivas\ABM\Http\Controllers\Controller as BaseController;

use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class ResourceController extends BaseController
{
    protected $formFieldSets;
    /**
     * @var Collection
     */
    protected $listColumnFields;

    /**
     * @var Model
     */
    protected $model;
    protected $actions;
    protected $defaultRelations = [];

    const actionAllowed = 'allowed';
    const actionDenied = 'denied';
    const actionViewOnly = 'view';

    use AuthorizesRequests;

    public function __construct()
    {
        parent::__construct();
        $this->initFormFields();
        $this->initActions();
        $this->initListColumns();
    }

    protected function initActions()
    {
        $this->actions = [
            [
                'action' => 'update'
            ],
            [
                'action' => 'delete'
            ]
        ];
    }

    protected function getActions()
    {
        return $this->actions;
    }

    protected function initFormFields()
    {
        $this->formFieldSets = [];
    }

    /**
     * Gets (creates if doesn't exists) the fieldSet with the given name
     * @param string $fieldSetName
     * @return FieldSet
     */
    protected function getFormFieldSet($fieldSetName)
    {
        if (!isset($this->formFieldSets[$fieldSetName])) {
            $this->addFieldSet(new FieldSet($fieldSetName));
        }
        return $this->formFieldSets[$fieldSetName];
    }

    /**
     * @param FieldSet $fieldSet
     * @return void
     */
    protected function addFieldSet($fieldSet)
    {
        $this->formFieldSets[$fieldSet->getName()] = $fieldSet;
    }

    protected function getRelations()
    {
        return $this->defaultRelations;
    }

    public function getFormFields()
    {
        $plainFiledsArray = [];
        /** @var FieldSet $fieldset */
        foreach ($this->formFieldSets as $fieldset) {
            $plainFiledsArray[$fieldset->getName()] = $fieldset->toArray();
        }
        return $plainFiledsArray;
    }

    /**
     * @param array $filterProperties
     * @param $user
     * @return mixed
     */
    protected function getPaginatedFilteredList(array $filterProperties, $user = null, $injectUserActions = true)
    {
        if (!isset($user)) {
            $user = auth()->user();
        }
        $q = $this->getListBaseQuery();
        $list = $q->filter($filterProperties)
            ->PaginateFilter(null, $this->getFieldListForFilter($this->modelFields()));

        if ($injectUserActions) {
            $items = $list->items();
            foreach ($items as $res) {
                $res->actions = $this->getUserActions($res, $user);
            }
        }
        return $list;
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        try {
            $this->authorize('viewAny', $this->model);
            $user = $request->user();

            $this->log('Visited', $this->getLogContext(), 'debug');
            $filterProperties = $request->all();
            $list = $this->getPaginatedFilteredList($filterProperties, $user);

            return $list;
        } catch (QueryException $e) {
            return response($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            return response($e->getMessage(), 403);
        }
    }

    /**
     * Display a listing of the resource.
     *
     * @return array|\Illuminate\Http\Response
     */
    public function options(Request $request)
    {
        try {
            $this->authorize('viewOptions', $this->model);
            $list = $this->model::filter($request->all())->get();
            $this->log('getOptions', $this->getLogContext(), 'debug');

            $options = [];
            foreach ($list as $resource) {
                $options[] = [
                    'id' => $resource->id,
                    'label' => $resource->label
                ];
            }
            return $options;
        } catch (QueryException $e) {
            return response($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            return response($e->getMessage(), 403);
        }
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        try {
            $this->validateFormFields($request);

            $this->authorize('create', $this->model);
            $created = $this->storeResource($request->all());
            $created->actions = $this->getUserActions($created, $request->user());
            $this->log('Resource Created', $this->getLogContext($created));
            return $created;
        } catch (QueryException $e) {
            return response($e->getMessage(), 409);
        } catch (AuthorizationException $e) {

            return response($e->getMessage(), 403);
        } catch (ABMException $e) {
            return $this->resolveException($e);
        } catch (FormFieldValidationException $e) {
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function resolveException(ABMException $e)
    {
        return response($e->getMessage(), $e->getStatusCode());
    }

    protected function fetchResourceForShow($id)
    {
        return $this->model::with($this->getRelations())->findOrFail($id);
    }

    /**
     * Display the specified resource.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function show(Request $request, $id)
    {
        try {
            $item = $this->fetchResourceForShow($id);
            $this->authorize('view', $item);
            $item->actions = $this->getUserActions($item, $request->user());
            return $item;
        } catch (QueryException $e) {
            return response($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            return response($e->getMessage(), 403);
        }
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param \App\Models\Model $resource
     * @return \Illuminate\Http\Response
     */
    public function edit(Model $resource)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     *
     * @param \Illuminate\Http\Request $request
     * @param int $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, int $id)
    {
        $toUpdate = $this->model::query()->where('id', $id)->first();

        try {
            $this->validateFormFields($request);
            $this->authorize('update', $toUpdate);
            $updated = $this->updateResource($request->all(), $id);
            $updated->actions = $this->getUserActions($updated, $request->user());
            $this->log('Resource Updated', $this->getLogContext($updated));
            return $updated;
        } catch (QueryException $e) {
            return response($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            return response($e->getMessage(), 403);
        } catch (FormFieldValidationException $e) {
            return response($e->getMessage(), $e->getCode());
        }
    }

    public function updateResource($data, int $id)
    {
        $updatedAt = $this->model::query()->where('id', $id)->update($data);
        if (isset($updatedAt)) {
            $updated = $this->model::query()->where('id', $id)->first();

            if (!empty($this->getRelations())) {
                $updated = $this->model::query()->with($this->getRelations())
                    ->where('id', $id)->first();
            }
            return $updated;
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param int $id
     * @return \Illuminate\Http\Response
     * @throws \Illuminate\Auth\Access\AuthorizationException
     */
    public function destroy($id)
    {
        $c = $this->model::find($id);
        try {
            if (!isset($c)) {
                throw new \Exception('El elemento no se encuentra');
            }
            $this->authorize('delete', $c);
            $this->log('Resource Deleted', [
                'user' => Auth::user()->name,
                'model' => $this->model,
                'id' => $id
            ]);
            return $c->delete();
        } catch (QueryException $e) {
            return response($e->getMessage(), 409);
        } catch (AuthorizationException $e) {
            return response($e->getMessage(), 403);
        } catch (\Exception $error) {
            return response($error->getMessage(), 404);
        }
    }

    /**
     * @return mixed
     */
    public function getListColumnFields()
    {
        if(!isset($this->listColumnFields))
            $this->initListColumns();
        return $this->listColumnFields;
    }

    /**
     * Every Resourse controller should define the columns to show on the ABM list
     */
    public function initListColumns()
    {
        $this->listColumnFields = new Collection();
    }

    /**
     * @param ColumnField $field
     * @return $this
     */
    public function addColumnField(ColumnField $field) {
        $this->listColumnFields->put($field->getName(), $field);
        return $this;
    }

    /**
     * @param $fieldName
     * @return $this
     */
    public function removeColumnField($fieldName) {
        $this->listColumnFields->forget($fieldName);
        return $this;
    }


    protected function populateFieldsFromModel() {
        $modelFields = (new $this->model())->getDisplayableColumns();
        foreach ($modelFields as $col) {
            $this->addColumnField(ColumnField::create($col,$col));
        }
    }


    /**
     * Returns the ordered (according to the listColumns alias array) list of fields for lists and forms
     * @return array
     */
    public function fields()
    {
        //$unsortedFields = array_merge($this->modelFields(), $this->relatedFields(), $this->appendedFields());

        if ($this->listColumnFields->isEmpty()) {
            $this->populateFieldsFromModel();
        }

        return $this->getListColumnFields();
    }

    /**
     * @param $type
     */
    protected function getColumnFieldsByType($type)
    {
        $typeFields = [];
        foreach ($this->listColumnFields as $columnField) {
            if (!isset($columnField)) {
                $columnField = new ColumnField();
            }
            if ($columnField->getFieldType() == $type) {
                $typeFields[] = $columnField;
            }
        }

        return $typeFields;
    }

    protected function getRelatedModelClass($parentModel, $relation)
    {
        if (!Str::contains($relation, '.')) {
            return get_class((new $parentModel())->$relation()->getRelated());
        }

        $subParentRelation = Str::before($relation, '.');
        return get_class((new $parentModel())->$subParentRelation()->getRelated());
    }

    /**
     * Sets and returns fields used in the ABM that are from the main model
     * @return mixed
     */
    public function modelFields()
    {
        $modelFields = [];
        $modelTypeFields = $this->getColumnFieldsByType(ColumnFieldType::modelField);

        if (empty($modelTypeFields)) {
            return ['*'];
        }

        foreach ($modelTypeFields as $modelField) {
            $modelFields[$modelField->getLabel()] = $modelField->getName();
        }

        $relatedModels = $this->getRelationsFromRelatedFields();

        foreach ($relatedModels as $relation) {
            $relatedModelClassName = $this->getRelatedModelClass($this->model, $relation);
            if (class_exists($relatedModelClassName)) {
                $fk = (new $relatedModelClassName())->getForeignKey();
                $modelFields[$fk] = $fk;
            }
        }


        return $modelFields;
    }

    /**
     * Sets and returns the fields used in list and forms of the ABM that come from related models to the main model
     * @return array
     */
    public function relatedFields()
    {
        $relatedFields = [];
        $relatedColumnFields = $this->getColumnFieldsByType(ColumnFieldType::relatedField);
        foreach ($relatedColumnFields as $relatedColumnField) {
            $relatedFields[$relatedColumnField->getLabel()] = $relatedColumnField->getName();
        }
        return $relatedFields;
    }

    /**
     * Sets and returns the fields used in list and forms of the ABM that come from appended properties in the main model
     * @return array
     */
    public function appendedFields()
    {
        $appendedFields = [];
        $appendedColumnFields = $this->getColumnFieldsByType(ColumnFieldType::appendedField);
        foreach ($appendedColumnFields as $appendedColumnField) {
            $appendedFields[$appendedColumnField->getLabel()] = $appendedColumnField->getName();
        }
        return $appendedFields;
    }

    public function formFields()
    {
        return $this->getFormFields();
    }

    public function fullFormFields()
    {
        return $this->getFullFormFields();
    }

    public function actions()
    {
        return $this->getActions();
    }

    /**
     * @param array $resourceValues
     * @return \Illuminate\Database\Eloquent\Builder|\Illuminate\Database\Eloquent\Model|object|null
     */
    public function storeResource(array $resourceValues)
    {
        $created = $this->model::query()->create($resourceValues);
        if (!empty($this->getRelations())) {
            $created = $this->model::query()->with($this->getRelations())
                ->where('id', $created->id)->first();
        }
        return $created;
    }

    protected function getEnumOptions($enumValues)
    {
        $enumOptions = [];
        foreach ($enumValues as $value) {
            $enumOptions[] = ['id' => $value, 'label' => $value, 'value' => $value, 'text' => $value];
        }
        return $enumOptions;
    }

    /**
     * @param int $id
     * @return mixed
     */
    protected function getResource(int $id)
    {
        return $this->model::query()
            ->with($this->getRelations())
            ->where('id', $id)
            ->first();
    }

    /**
     * @param $resource
     * @return array
     */
    protected function getLogContext($resource = null): array
    {
        $context = [
            'user' => Auth::user()->name,
            'model' => $this->model
        ];
        try {
            if (isset($resource)) {
                $context['id'] = $resource->id;
                $context['name'] = $resource->name;
            }
        } catch (\ErrorException $exception) {
        }

        return $context;
    }

    /**
     * @param Model $resource
     * @param User $user
     * @return array
     */
    protected function getUserActions($resource, $user)
    {
        $allowedActions = [];
        foreach ($this->actions as $action) {
            $userActionPermission = $this->getPermissionForAction($user, $action['action'], $resource);
            $allowedActions[$action['action']] = $userActionPermission;
        }
        return $allowedActions;
    }

    /**
     * @param User $user
     * @param string $action
     * @param \App\Models\Model $resource
     * @return array
     */
    protected function getPermissionForAction($user, string $action, $resource)
    {
        $userActionPermission['any'] = ResourceController::actionDenied;

        if ($user->can($action . 'Any', $resource)) {
            $userActionPermission['any'] = ResourceController::actionAllowed;
            $userActionPermission['action'] = ResourceController::actionDenied;

            if ($user->can($action, $resource)) {
                $userActionPermission['action'] = ResourceController::actionAllowed;
            }
        }
        return $userActionPermission;
    }

    /**
     * Gets the "with" models from the dotted fields (used with related)
     * @return array
     */
    protected function getRelationsFromRelatedFields(): array
    {
        return collect($this->relatedFields())
            ->map(function ($field) {
                return Str::camel(Str::beforeLast($field, '.'));
            })
            ->unique()
            ->values()
            ->all();
    }

    /**
     * @param $modelFields
     * @return string[]
     */
    protected function getFieldListForFilter($modelFields): array
    {
        if (empty($modelFields)) {
            $modelFields = ['*'];
        } else {
            $modelFields[] = 'id';
        }

        return $modelFields;
    }


    /**
     * @param $request
     * @throws FormFieldValidationException
     */
    public function validateFormFields($request)
    {
        $fieldsWithErrors = [];
        /** @var FieldSet $fieldset */
        foreach ($this->formFieldSets as $fieldset) {
            /** @var FormField $field */
            foreach ($fieldset->getFields() as $field) {
                $validField = $field->validate($request[$field->getName()]);
                if (!$validField) {
                    $fieldsWithErrors[] = $field;
                }
            }
            /** @var FieldGroup $group */
            foreach ($fieldset->getFieldGroups() as $group) {
                foreach ($group->getFields() as $field) {
                    $validField = $field->validate($request[$field->getName()]);
                    if (!$validField) {
                        $fieldsWithErrors[] = $field;
                    }
                }
            }
        }
        if (!empty($fieldsWithErrors)) {
            $errorMsg = [];
            foreach ($fieldsWithErrors as $fe) {
                $errorMsg[$fe->getName()] = $fe->getErrorMessage();
            }
            throw new FormFieldValidationException(json_encode($errorMsg), 422);
        }
    }

    /**
     * @return \Illuminate\Database\Eloquent\Builder
     */
    protected function getListBaseQuery(): \Illuminate\Database\Eloquent\Builder
    {
        $with = $this->getRelationsFromRelatedFields();
        return $$this->model::with($with);
    }


}
