<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use Illuminate\Http\Request;
use App\Models\Donation;
use App\Models\Members;
use App\Models\About;
use App\Models\Album;
use App\Models\Albums;

class HomeController extends Controller
{
    public function index()
    {
        return view('index');
    }

    public function donate()
    {
        // FIX: Tambahkan filter add_to_leaderboard = 'yes'
        $donors = Donation::select('donation.*')
            ->whereIn('status', ['paid', 'recorded_on_chain'])
            ->where('add_to_leaderboard', 'yes') // Hanya tampilkan jika user setuju
            ->orderBy('amount', 'desc')
            ->limit(5)
            ->get();

        return view('donate', compact('donors'));
    }

    public function contact()
    {
        return view('contact');
    }
    public function about()
    {
        $member=Members::all();
        return view('about',compact('member'));
    }
    public function albums()
    {
        $albums = Albums::select('*')
            ->with('media')
            ->where('albums.status', 1)
            ->orderBy('id', 'DESC')
            ->paginate(12);

        return view('albums',compact('albums'));
    }

    public function album($id)
    {
        $album = Albums::where('id', $id)
            ->with('media')
            ->where('albums.status', 1)
            ->first();

        if (!$album) return redirect()->route('home.albums')->with('error', 'Album does not exists');

        return view('album', compact('album'));
    }

    public function contactSubmit(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:50',
            'email' => 'required|email',
            'mobile' => 'required|numeric',
            'message' => 'required|string',
        ]);
        try {
            $contact = new Contact();
            $contact->name = $request->name;
            $contact->email = $request->email;
            $contact->mobile = $request->mobile;
            $contact->message = $request->message;
            $contact->save();
            return redirect()->back()->with(['success' => 'Contact Form submited successfully']);
        } catch (\Exception $e) {
            return redirect()->back()->with(['error' => 'Something went wrong.[' . $e->getMessage() . ']'])->withInput();
        }
    }
    public function privacy()
    {
        return view('privacy');
    }

    public function leaderboard()
    {
        // FIX: Tambahkan filter add_to_leaderboard = 'yes'
        $donors = Donation::select('donation.*')
            ->whereIn('status', ['paid', 'recorded_on_chain'])
            ->where('add_to_leaderboard', 'yes') // Hanya tampilkan jika user setuju
            ->orderBy('amount', 'desc')
            ->paginate(10);

        return view('leaderboard', compact('donors'));
    }

}