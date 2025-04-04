<?php

namespace App\Http\Controllers;

use App\Models\Comment;
use App\Models\Post;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CommentController extends Controller
{
    public function store(Request $request, Comment $comment)
    {
        $request->validate([
            'content' => 'required|string'
        ]);

        $comment->create([
            'user_id' => Auth::id(),
            'post_id' => Auth::id(),
            
            'content' => $request->content,
            
            
        ]);

        return redirect()->back()->with('success', 'Comment added successfully!');
    }
}
