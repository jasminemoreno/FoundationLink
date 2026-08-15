<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Campaign;
use App\Models\CampaignUpdate;
use App\Models\Donation;
use App\Models\Foundation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class FoundationFollowController extends Controller
{
    /* ══════════════════════════════
       FOLLOW
    ══════════════════════════════ */
    public function follow($foundationId)
    {
        $foundation = Foundation::where('status', 'verified')->findOrFail($foundationId);
        $donor = auth()->user();

        // only insert if not already following
        $exists = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $foundationId)
            ->where('type', 'follow')
            ->exists();

        if (!$exists) {
            DB::table('foundation_followers')->insert([
                'donor_id' => $donor->id,
                'foundation_id' => $foundationId,
                'type' => 'follow',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'You are now following ' . $foundation->name,
            'following' => true,
            'followers_count' => $foundation->followers()->count(),
        ]);
    }

    /* ══════════════════════════════
       UNFOLLOW
    ══════════════════════════════ */
    public function unfollow($foundationId)
    {
        $foundation = Foundation::findOrFail($foundationId);
        $donor = auth()->user();

        DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $foundationId)
            ->where('type', 'follow')
            ->delete();

        return response()->json([
            'message' => 'You have unfollowed ' . $foundation->name,
            'following' => false,
            'followers_count' => $foundation->followers()->count(),
        ]);
    }

    /* ══════════════════════════════
       LIKE
    ══════════════════════════════ */
    public function like($foundationId)
    {
        $foundation = Foundation::where('status', 'verified')->findOrFail($foundationId);
        $donor = auth()->user();

        $exists = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $foundationId)
            ->where('type', 'like')
            ->exists();

        if (!$exists) {
            DB::table('foundation_followers')->insert([
                'donor_id' => $donor->id,
                'foundation_id' => $foundationId,
                'type' => 'like',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        return response()->json([
            'message' => 'You liked ' . $foundation->name,
            'liked' => true,
            'likes_count' => $foundation->likers()->count(),
        ]);
    }

    /* ══════════════════════════════
       UNLIKE
    ══════════════════════════════ */
    public function unlike($foundationId)
    {
        $foundation = Foundation::findOrFail($foundationId);
        $donor = auth()->user();

        DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $foundationId)
            ->where('type', 'like')
            ->delete();

        return response()->json([
            'message' => 'You unliked ' . $foundation->name,
            'liked' => false,
            'likes_count' => $foundation->likers()->count(),
        ]);
    }

    /* ══════════════════════════════
       MY FOLLOWED FOUNDATIONS
    ══════════════════════════════ */
    public function myFollowed()
    {
        $donor = auth()->user();
        $foundations = $donor->followedFoundations()->with('category')->get();

        return response()->json($foundations->map(function ($f) {
            return [
                'id' => $f->id,
                'name' => $f->name,
                'logo' => $f->logo,
                'category' => $f->category?->name,
                'city_municipality' => $f->city_municipality,
                'province' => $f->province,
            ];
        }));
    }

    /* ══════════════════════════════
       CHECK FOLLOW + LIKE STATUS
    ══════════════════════════════ */
    public function checkStatus($foundationId)
    {
        $donor = auth()->user();
        $foundation = Foundation::findOrFail($foundationId);

        $isFollowing = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $foundationId)
            ->where('type', 'follow')
            ->exists();

        $isLiked = DB::table('foundation_followers')
            ->where('donor_id', $donor->id)
            ->where('foundation_id', $foundationId)
            ->where('type', 'like')
            ->exists();

        return response()->json([
            'following' => $isFollowing,
            'liked' => $isLiked,
            'followers_count' => $foundation->followers()->count(),
            'likes_count' => $foundation->likers()->count(),
        ]);
    }

    /* ══════════════════════════════
       DONOR FEED
    ══════════════════════════════ */
    public function feed(Request $request)
    {
        $donor = auth()->user();
        $filter = $request->query('filter', 'all');

        $followedFoundationIds = $donor->followedFoundations()->pluck('foundations.id');

        $campaignIdsFromFollowed = Campaign::whereIn('foundation_id', $followedFoundationIds)
            ->pluck('id');

        $campaignIdsFromDonations = Donation::where('donor_id', $donor->id)
            ->pluck('campaign_id')
            ->unique();

        if ($filter === 'followed') {
            $campaignIds = $campaignIdsFromFollowed;
        } elseif ($filter === 'donated') {
            $campaignIds = $campaignIdsFromDonations;
        } else {
            $campaignIds = $campaignIdsFromFollowed->merge($campaignIdsFromDonations)->unique();
        }

        $updates = CampaignUpdate::whereIn('campaign_id', $campaignIds)
            ->with([
                'campaign:id,title,foundation_id,cover_photo',
                'campaign.foundation:id,name,logo',
                'reactions',
            ])
            ->latest()
            ->paginate(15);

        $userId = $donor->id;

        $items = $updates->getCollection()->map(fn($u) => [
            'id' => $u->id,
            'title' => $u->title,
            'content' => $u->content,
            'photos' => $u->photos ?? [],
            'created_at' => $u->created_at,
            'posted_at' => $u->created_at->diffForHumans(),
            'campaign' => [
                'id' => $u->campaign->id,
                'title' => $u->campaign->title,
                'cover_photo' => $u->campaign->cover_photo,
            ],
            'foundation' => [
                'id' => $u->campaign->foundation->id,
                'name' => $u->campaign->foundation->name,
                'logo' => $u->campaign->foundation->logo,
            ],
            'total_reactions' => $u->reactions->count(),
            'my_reaction' => $u->reactions->contains('donor_id', $userId),
        ]);

        return response()->json([
            'data' => $items,
            'current_page' => $updates->currentPage(),
            'last_page' => $updates->lastPage(),
            'per_page' => $updates->perPage(),
            'total' => $updates->total(),
        ]);
    }
}