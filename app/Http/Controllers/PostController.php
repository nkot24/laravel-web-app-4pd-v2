<?php

namespace App\Http\Controllers;

use App\Models\Post;
use App\Models\PostStatus;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Gate;

class PostController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $userId = Auth::id();

        // $posts = Post::join('post_statuses', 'posts.status_id', '=', 'post_statuses.id')
        //     ->where(function ($query) use ($userId) {
        //         $query->where('post_statuses.name', PostStatus::STATUS_PUBLIC)
        //             ->orWhere('posts.user_id', $userId);
        //     })
        //     ->select('posts.*') // Select all columns from the posts table
        //     ->get();

        $posts = Post::with('status')
            ->where(function ($query) use ($userId) {
                $query->whereHas('status', function ($query) {
                    $query->where('name', PostStatus::STATUS_PUBLIC);
                })
                ->orWhere(function ($query) use ($userId) {
                    $query->where('user_id', $userId)
                        ->whereHas('status', function ($query) {
                            $query->where('name', PostStatus::STATUS_PRIVATE); // Assuming you have a constant for private status
                        });
                });
            })
            ->get();

        return view('post.index', ['posts' => $posts]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $statuses = PostStatus::all();
        return view('post.create', ['statuses' => $statuses]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status_id' => 'required|exists:post_statuses,id',
        ]);

        $post = new Post();
        $post->title = $request->title;
        $post->content = $request->content;
        $post->status_id = $request->status_id;
        $post->user_id = Auth::id();

        if ($request->hasFile('image')) {
            $path = $request->file('image')->store('images', 'public');
            $post->image_path = $path;
        }

        $post->save();

        return redirect()->route('posts.index')->with('success', 'Post created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Post $post)
    {
        Gate::authorize('view', $post);
        return view('post.show', ['post' => $post]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Post $post)
    {
        Gate::authorize('update', $post);
        $statuses = PostStatus::all();
        return view('post.edit', ['post' => $post, 'statuses' => $statuses]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Post $post)
    {
        Gate::authorize('update', $post);
        $request->validate([
            'title' => 'required|string|max:255',
            'content' => 'required|string',
            'image' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
            'status_id' => 'required|exists:post_statuses,id',
        ]);

        $post->title = $request->title;
        $post->content = $request->content;
        $post->status_id = $request->status_id;

        if ($request->hasFile('image')) {
            if ($post->image_path) {
                \Storage::disk('public')->delete($post->image_path);
            }
            $path = $request->file('image')->store('images', 'public');
            $post->image_path = $path;
        }
        
        // Image removal without replacement has to be done in separate method

        $post->save();

        return redirect()->route('posts.index')->with('success', 'Post updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Post $post)
    {
        Gate::authorize('delete', $post);
        $post->delete();
        if ($post->image_path) {
            \Storage::disk('public')->delete($post->image_path);
        }
        return redirect()->route('posts.index')->with('success', 'Post deleted successfully.');
    }
}
