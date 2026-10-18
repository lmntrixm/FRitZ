<?php

namespace App\Http\Controllers;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\Protocol;
use App\Models\ShowcaseProject;
use App\Support\CatalogFilter;
use App\Support\Fa;
use Illuminate\Http\Request;

class CatalogController extends Controller
{
    public function home()
    {
        $featured = Product::catalog()->where('is_featured', true)->orderBy('sort')->limit(8)->get();
        $total = Product::published()->count();
        $brands = Brand::active()->whereHas('products', fn ($q) => $q->published())->get();

        return view('catalog.home', [
            'hero' => $featured->first(),
            'featured' => $featured,
            'cats' => $this->leaves(),
            'brands' => $brands,
            // honest numbers: «بیش از ۴۰۰» only once there really are 400+
            'totalLabel' => $total >= 100 ? 'بیش از '.Fa::digits(intdiv($total, 100) * 100) : Fa::digits($total),
            'brandCount' => Fa::digits($brands->count()),
        ]);
    }

    public function category(Request $request, Category $category)
    {
        abort_unless($category->is_active && $category->children()->doesntExist(), 404);
        $filter = new CatalogFilter($category, $request);
        $results = $filter->results();
        $n = $filter->activeCount();

        return view('catalog.category', [
            'category' => $category,
            'filter' => $filter,
            'results' => $results,
            'groups' => $filter->groups(),
            'active' => $filter->active(),
            'activeN' => $n,
            'sub' => $n ? Fa::digits($results->count()).' نتیجه' : Fa::digits($filter->all->count()).' محصول',
            'cats' => $this->leaves(),
            'openSheet' => $request->boolean('sheet'),
        ]);
    }

    public function about()
    {
        return view('catalog.about', [
            'protocols' => Protocol::active()->get(),
            'brands' => Brand::active()->get(),
            'projects' => ShowcaseProject::query()->where('is_active', true)->orderBy('sort')->with('media')->get(),
        ]);
    }

    private function leaves()
    {
        return Category::leaves()->withCount(['products' => fn ($q) => $q->published()])->get();
    }
}
