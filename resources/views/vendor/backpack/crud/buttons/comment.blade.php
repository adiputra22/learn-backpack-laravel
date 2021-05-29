@if ($crud->hasAccess('comment'))
	<!-- Single edit button -->
	<a href="{{ url($crud->route.'/'.$entry->getKey().'/articlecomment') }}" class="btn btn-sm btn-link"><i class="la la-eye"></i> {{ trans('backpack::crud.comment') }}</a>
@endif