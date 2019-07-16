<?php

namespace App\Http\Controllers\Models;

use App\Http\Controllers\Controller;
use App\Http\Requests\CategoryDeleteRequest;
use App\Http\Requests\CategoryStoreRequest;
use App\Http\Requests\CategoryUpdateRequest;
use App\Models\Category;
use App\Repositories\CategoryRepository;
use Illuminate\Http\Request;

class CategoryController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param Request $request
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function index(Request $request)
    {
        $categories = Category::byUser(auth()->id())->parentOnly();

        if ($request->has('orderBy') && $request->has('orderDir')) {
            $categories->orderBy($request->get('orderBy'), $request->get('orderDir'));
        } else {
            $categories->orderBy('name', 'ASC');
        }

        $categories = $categories->paginate(getPaginationLimit());

        return view('models.categories.index', [
            'categories' => $categories,
            'route' => $request->getBaseUrl(),
            'order_by' => $request->get('orderBy'),
            'order_dir' => $request->get('orderDir'),
        ]);
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function create()
    {
        return view('models.categories.create')
            ->with('categories', Category::parentOnly()->orderBy('name', 'asc')->get());
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param CategoryStoreRequest $request
     * @return
     */
    public function store(CategoryStoreRequest $request)
    {
        $data = $request->except(['tags', 'reload_view']);

        $category = CategoryRepository::create($data);

        alert(trans('category.added_successfully'), 'success');

        if ($request->get('reload_view')) {
            session()->flash('reload_view', true);
            return redirect()->route('categories.create');
        }

        return redirect()->route('categories.show', [$category->id]);
    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param  int    $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function show(Request $request, $id)
    {
        $category = Category::find($id);

        if (empty($category)) {
            abort(404);
        }

        if ($category->user_id !== auth()->id()) {
            abort(403);
        }

        $links = $category->links()->byUser(auth()->id());

        if ($request->has('orderBy') && $request->has('orderDir')) {
            $links->orderBy($request->get('orderBy'), $request->get('orderDir'));
        } else {
            $links->orderBy('created_at', 'DESC');
        }

        $links = $links->paginate(getPaginationLimit());

        return view('models.categories.show', [
            'category' => $category,
            'category_links' => $links,
            'route' => $request->getBaseUrl(),
            'order_by' => $request->get('orderBy'),
            'order_dir' => $request->get('orderDir'),
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int $id
     * @return \Illuminate\Contracts\View\Factory|\Illuminate\View\View
     */
    public function edit($id)
    {
        $category = Category::find($id);

        if (empty($category)) {
            abort(404);
        }

        if ($category->user_id !== auth()->id()) {
            abort(403);
        }

        return view('models.categories.edit')
            ->with('categories', Category::parentOnly()->orderBy('name', 'asc')->get())
            ->with('category', $category);
    }

    /**
     * Update the specified resource in storage.
     *
     * @param CategoryUpdateRequest $request
     * @param  int                  $id
     * @return
     */
    public function update(CategoryUpdateRequest $request, $id)
    {
        $category = Category::find($id);

        if (empty($category)) {
            abort(404);
        }

        if ($category->user_id !== auth()->id()) {
            abort(403);
        }

        $data = $request->all();
        $category = CategoryRepository::update($category, $data);

        alert(trans('category.updated_successfully'), 'success');

        return redirect()->route('categories.show', [$category->id]);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param CategoryDeleteRequest $request
     * @param  int                  $id
     * @return \Illuminate\Http\RedirectResponse
     * @throws \Exception
     */
    public function destroy(CategoryDeleteRequest $request, $id)
    {
        $category = Category::find($id);

        if (empty($category)) {
            abort(404);
        }

        if ($category->user_id !== auth()->id()) {
            abort(403);
        }

        $deletion_successfull = CategoryRepository::delete($category);

        if (!$deletion_successfull) {
            alert(trans('category.deletion_error'), 'error');
            return redirect()->back();
        }

        alert(trans('category.deleted_successfully'), 'warning');
        return redirect()->route('categories.index');
    }
}
