<?php

namespace Askvortsov\TrustLevels\Tests\integration\range;

use Carbon\Carbon;
use Flarum\Http\AccessToken;
use Flarum\Testing\integration\RetrievesAuthorizedUsers;
use Flarum\Testing\integration\TestCase;
use Flarum\User\Event\LoggedIn;
use Flarum\User\User;

class DiscussionsStartedTest extends TestCase
{
    use RetrievesAuthorizedUsers;
    use UsesRange;

    /**
     * @inheritDoc
     */
    protected function setUp()
    {
        parent::setUp();

        $this->extension('askvortsov-trust-levels');

        $this->prepareDatabase([
            'users' => [
                $this->normalUser(),
            ],
            'discussions' => [
                ['id' => 1, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'best_answer_user_id' => 2],
                ['id' => 2, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'best_answer_user_id' => 2],
                ['id' => 3, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'best_answer_user_id' => 2],
                ['id' => 4, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'best_answer_user_id' => 2],
                ['id' => 5, 'title' => __CLASS__, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'first_post_id' => 1, 'comment_count' => 1, 'best_answer_user_id' => 2],
            ],
            'posts' => [
                ['id' => 1, 'discussion_id' => 1, 'created_at' => Carbon::now()->toDateTimeString(), 'user_id' => 2, 'type' => 'comment', 'content' => '<t><p>foo bar</p></t>']
            ],
        ]);
    }

    /**
     * @test
     */
    public function not_added_to_group_by_default()
    {
        $this->app()->getContainer()->make('events')->dispatch(new LoggedIn(User::find(2), new AccessToken([])));

        $this->assertNotContains('4', User::find(2)->groups->pluck('id')->all());
    }

    /**
     * @test
     */
    public function added_to_group_properly()
    {
        $this->prepareDatabase(['trust_levels' => [
            $this->genTrustLevel('discussions started', 4, [
                'discussions_started' => [2, 10]
            ])
        ]]);

        $this->app();
        User::find(2)->refreshDiscussionCount()->save();
        $this->app()->getContainer()->make('events')->dispatch(new LoggedIn(User::find(2), new AccessToken([])));

        $this->assertContains('4', User::find(2)->groups->pluck('id')->all());
    }

    /**
     * @test
     */
    public function not_added_to_group_if_doesnt_apply()
    {
        $this->prepareDatabase(['trust_levels' => [
            $this->genTrustLevel('discussions started', 4, [
                'discussions_started' => [-1, 4]
            ]),
            $this->genTrustLevel('discussions started', 4, [
                'discussions_started' => [1, 4]
            ]),
            $this->genTrustLevel('discussions started', 4, [
                'discussions_started' => [6, 8]
            ]),
            $this->genTrustLevel('discussions started', 4, [
                'discussions_started' => [6, -1]
            ])
        ]]);

        $this->app();
        User::find(2)->refreshDiscussionCount()->save();
        $this->app()->getContainer()->make('events')->dispatch(new LoggedIn(User::find(2), new AccessToken([])));

        $this->assertNotContains('4', User::find(2)->groups->pluck('id')->all());
    }
}
