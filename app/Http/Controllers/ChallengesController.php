<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Auth;
use App\Challenge;
use App\Category;
use App\ChallengeCategory;
use App\Solved;
use App\Score;
use App\Attachment;
use App\User;
use Carbon\Carbon;

class ChallengesController extends Controller
{
    public function store(Request $request)
    {
    	$challenge = array(
            'title' => $request->get('inputTitle'),
            'score' => $request->get('inputScore'),
            'flag' => $request->get('inputFlag'),
            'content' => $request->get('inputContent'),
        );

        Challenge::create($challenge);

        $category = array();
        $category['category'] = $request->get('inputCategory');

        if($request->has('inputCategory')) {
            $get_challenge = Challenge::orderBy('updated_at', 'DESC')->first();
            $get_category = Category::where('category', $category['category'])->first();
            $challenge_id = $get_challenge->id;
            $category_id = $get_category->id;
            $category['challenge_id'] = $challenge_id;
            $category['category_id'] = $category_id;
        }

        if(!empty($category)) {
            ChallengeCategory::create($category);
        }

        $attachment = array();

        if($request->has('inputURL') || $request->has('inputFile')) {

            $get_challenge = Challenge::orderBy('updated_at', 'DESC')->first();
            $challenge_id = $get_challenge->id;
            $attachment['challenge_id'] = $challenge_id;

            if($request->has('inputURL')) {
                $attachment['url'] = $request->get('inputURL');
            }

            if($request->hasFile('inputFile')) {
                $attachment_file = $request->file('inputFile');
                $directory = 'public/challenges';
                $file = $attachment_file->getClientOriginalName();
                $ext = $attachment_file->getClientOriginalExtension();
                $attachment_file->storeAs($directory, $file);                
            }
        }

        if($request->hasFile('inputFile')) {
            $attachment['filename'] = $directory . '/' . $file;            
        }

        if(!empty($attachment)) {
            Attachment::create($attachment);
        }

    	return redirect()->route('admin.challenges')->with('success', 'Challenge saved!');
    }

    public function create()
    {
        $categories = Category::all();
    	return view('admin.challenges.create')
            ->with('categories', $categories);
    }

    public function indexAdmin()
    {
        $challenges = Challenge::all();
        return view('admin.challenges')
            ->with('challenges', $challenges);
    }

    public function indexUser()
    {
        $user_id = Auth::user()->id;
        $solved = Solved::where('user_id', $user_id)->get();
        $challenges = Challenge::whereNotIn('id', $solved)->get();
        $categories = Category::all();
        return view('challenges')
            ->with('challenges', $challenges)
            ->with('categories', $categories);
    }

    public function edit($id)
    {
        $attachments = Attachment::all();
        $challenges = Challenge::find($id);
        $categories = Category::all();
        return view('admin.challenges.edit')
            ->with('attachments', $attachments)
            ->with('categories', $categories)
            ->with('challenge', $challenges);
    }

    public function update(Request $request, $id)
    {
        $challenge = Challenge::find($id);
        $attachment = Attachment::find($id);
        $challenge->category = $request->get('inputCategory');
        $challenge->title = $request->get('inputTitle');
        $challenge->score = $request->get('inputScore');
        $challenge->flag = $request->get('inputFlag');
        $challenge->content = $request->get('inputContent');
        // Update the attachment file
        if($request->hasFile('inputFile')) {
            $attachment_file = $request->file('inputFile');
            $directory = 'public/challenges';
            $file = $attachment_file->getClientOriginalName();
            $ext = $attachment_file->getClientOriginalExtension();
            $attachment_file->storeAs($directory, $file);
        }
        // Update the attachment database entries
        $attachment->url = $request->get('inputURL');
        $attachment->update();
        $challenge->update();
        return redirect()->route('user.challenges')->with('success', 'Challenge updated!');
    }

    public function destroy($id)
    {
        $challenge = Challenge::find($id);
        $challenge->delete();
        return redirect()->route('user.challenges')->with('success', 'Challenge deleted!');
    }

    public function submitFlag(Request $request)
    {
        $challenge = Challenge::find($request->id);
        $flag = $challenge->flag;

        $submit = array(
            'flag' => $request->get('flag'),
        );

        if($flag == $submit['flag']) {
            $solved = new Solved;
            $solved->challenge_id = $request->id;
            $solved->user_id = $request->user()->id;
            $solved->save();
            $this->addScore($request);
            return redirect('/challenges')->with('message', 'Correct Flag, Congratulations!');
        } else {
            return redirect('/challenges')->with('message', 'Try Again!');
        } 
    }

    public function addScore(Request $request)
    {
        $challenge = Challenge::find($request->id);
        $score = $challenge->score;
        $user = $request->user()->id;
        $user_score = Score::where('user_id', $user)->first();

        if(!$user_score) {
            $user_score = Score::create([
                'user_id' => $user,
                'score' => 0
            ]);
        }

        Score::where('user_id', $user)
            ->increment('score', $score, ['updated_at' => Carbon::now()]);
    }

    public function download($id)
    {
        try {
            $attachment = Attachment::where('challenge_id', $id)->first();
            $storage_path = storage_path('app/' . $attachment->filename);
            return response()->download($storage_path);
        } catch(Exception $e) {
            return abort(404);
        }
    }
}
