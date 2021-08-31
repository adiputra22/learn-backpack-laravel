<?php

namespace App\Http\Controllers\Admin;

use App\Http\Requests\ArticleRequest;
use App\Models\Article;
use App\Models\Category;
use Backpack\CRUD\app\Http\Controllers\CrudController;
use Backpack\CRUD\app\Library\CrudPanel\CrudPanelFacade as CRUD;

/**
 * Class ArticleCrudController
 * @package App\Http\Controllers\Admin
 * @property-read \Backpack\CRUD\app\Library\CrudPanel\CrudPanel $crud
 */
class ArticleCrudController extends CrudController
{
    use \Backpack\CRUD\app\Http\Controllers\Operations\ListOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\CreateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\UpdateOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\DeleteOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\ShowOperation;
    use \Backpack\CRUD\app\Http\Controllers\Operations\FetchOperation;

    /**
     * Configure the CrudPanel object. Apply settings to all operations.
     * 
     * @return void
     */
    public function setup()
    {
        CRUD::setModel(\App\Models\Article::class);
        CRUD::setRoute(config('backpack.base.route_prefix') . '/article');
        CRUD::setEntityNameStrings('article', 'articles');
    }

    /**
     * Define what happens when the List operation is loaded.
     * 
     * @see  https://backpackforlaravel.com/docs/crud-operation-list-entries
     * @return void
     */
    protected function setupListOperation()
    {
        CRUD::column('category_id');
        CRUD::column('title');
        CRUD::column('slug');
        CRUD::column('status')->wrapper([
            'element' => 'span',
            'class' => function ($crud, $column, $entry, $related_key) {
                if ($column['text'] == Article::STATUS_PUBLISHED) {
                    return 'badge badge-success';
                }

                return 'badge badge-default';
            },
        ]);

        CRUD::column('featured')->type('boolean');
        
        CRUD::column('date')->type('closure')->function(function($entry) {
            return 'Created on '.$entry->created_at;
        });

        
        $this->crud->enableBulkActions();
        
        $this->crud->addButtonFromView('line', 'comment', 'comment', 'end');
        
        // simple filter
        $this->crud->addFilter([
            'type'  => 'simple',
            'name'  => 'featured',
            'label' => 'Featured'
        ], 
        false, 
        function() { // if the filter is active
            $this->crud->addClause('where', 'featured', '1'); // apply the "active" eloquent scope 
        } );

        $this->crud->addFilter([
            'type' => 'simple',
            'name' => 'status_published',
            'label' => 'Status Published'
        ], false, function() {
            $this->crud->addClause('where', 'status', 1);
        });

        $this->crud->addFilter([
            'name'  => 'status',
            'type'  => 'select2',
            'label' => 'Category'
        ], function () {
            $categories = Category::all();
            return $categories->pluck('name', 'id')->toArray();
        }, function ($value) { // if the filter is active
            $this->crud->addClause('where', 'category_id', $value);
        });

        $this->crud->addFilter(
            [
                'name'        => 'category_id',
                'type'        => 'select2_ajax',
                'label'       => 'Category',
                'placeholder' => 'Pick a category',
                'method' => 'POST', // mandatory change
                'select_attribute' => 'name', // the attribute that will be shown to the user by default 'name'
                'select_key' => 'id' // by default is ID, change it if your model uses some other key
            ],
            backpack_url('article/fetch/category'), // the fetch route on the ProductCrudController 
            function($value) { // if the filter is active
                $this->crud->addClause('where', 'category_id', $value);
            }
        );

        /**
         * Columns can be defined using the fluent syntax or array syntax:
         * - CRUD::column('price')->type('number');
         * - CRUD::addColumn(['name' => 'price', 'type' => 'number']); 
         */
    }

    /**
     * Define what happens when the Create operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-create
     * @return void
     */
    protected function setupCreateOperation()
    {
        CRUD::setValidation(ArticleRequest::class);

        CRUD::field('category_id');
        CRUD::field('title');
        CRUD::field('slug');
        CRUD::field('content');
        CRUD::field('image');
        CRUD::field('status')->type('enum');
        CRUD::field('date');
        CRUD::field('featured');

        /**
         * Fields can be defined using the fluent syntax or array syntax:
         * - CRUD::field('price')->type('number');
         * - CRUD::addField(['name' => 'price', 'type' => 'number'])); 
         */
    }

    /**
     * Define what happens when the Update operation is loaded.
     * 
     * @see https://backpackforlaravel.com/docs/crud-operation-update
     * @return void
     */
    protected function setupUpdateOperation()
    {
        $this->setupCreateOperation();
    }

    protected function fetchCategory()
    {
        return $this->fetch([
            'model' => \App\Models\Category::class, // required
            'searchable_attributes' => ['name'],
            'paginate' => 10, // items to show per page
        ]);
    }
}
