<?php

namespace OiLab\OiLaravelChangelogs\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Inertia\Inertia;
use Inertia\Response;
use OiLab\OiLaravelChangelogs\Data\ChangeLogAnswerData;
use OiLab\OiLaravelChangelogs\OiLaravelChangelogs;
use OiLab\OiLaravelChangelogs\Services\ChangeLogEntry;
use OiLab\OiLaravelChangelogs\Services\ChangeLogRepository;

/**
 * The journal of what was fixed and what was improved.
 *
 * Read only, and there is no writing endpoint to add later: an entry is a
 * file of the repository, written with the fix it describes and reviewed with
 * it. A screen able to add one would be a second journal nobody diffs.
 *
 * One screen, two routes. The list and the entry are the same page — a column
 * of rows and the one being read — so `show` renders the same component as
 * `index` with an entry named. That is what makes an entry linkable: a slug
 * pasted into a chat opens on the entry rather than on the top of the list.
 */
class ChangeLogController extends Controller
{
    public function __construct(private readonly ChangeLogRepository $changeLogs) {}

    /**
     * The journal, opened on the first entry of the page being read.
     *
     * Landing on an empty right-hand pane would ask the reader to click before
     * reading anything, and the answer to "what changed" is almost always the
     * entry at the top. Paging the list moves the pane with it, so the two
     * halves of the screen never show different weeks.
     */
    public function index(Request $request): Response
    {
        $perPage = OiLaravelChangelogs::perPage();
        $page = max(1, (int) $request->integer('page', 1));

        return $this->render(
            $this->changeLogs->all()->values()->get(($page - 1) * $perPage),
            $page,
        );
    }

    /**
     * One entry.
     *
     * Two answers behind one URL. The page swaps its right-hand pane with a
     * plain fetch rather than a visit, so the column keeps its scroll; that
     * request asks for json and gets the entry alone, while an Inertia visit
     * and a browser opening the link cold both get the whole screen. The same
     * route is therefore the json the pane reads and the page the link opens,
     * and neither shape can drift from the other.
     */
    public function show(Request $request, string $slug): Response|JsonResponse
    {
        $entry = $this->changeLogs->find($slug);

        abort_if($entry === null, 404);

        if ($request->expectsJson()) {
            ['previous' => $previous, 'next' => $next] = $this->changeLogs->adjacent($slug);

            return response()->json(new ChangeLogAnswerData(
                entry: $this->changeLogs->detail($entry),
                previous: $previous,
                next: $next,
            ));
        }

        return $this->render($entry, $this->changeLogs->pageOf($entry, OiLaravelChangelogs::perPage()));
    }

    /**
     * Draw the screen around the entry being read.
     *
     * The page of the list is the one holding that entry rather than the first
     * one, so a link to an old entry opens with its own row in view and
     * highlighted instead of a column the reader has to page through.
     */
    private function render(?ChangeLogEntry $entry, int $page): Response
    {
        $index = route(OiLaravelChangelogs::routeName('index'));

        /*
         * The paginator is always pointed at the index, never at the url being
         * served: a page link built on /change-logs/{slug} would come back to
         * `show`, which reads the slug and ignores the page entirely.
         */
        return Inertia::render(OiLaravelChangelogs::component(), [
            'items' => $this->changeLogs->paginate(OiLaravelChangelogs::perPage(), $page, $index),
            'entry' => $entry === null ? null : $this->changeLogs->detail($entry),
            'baseUrl' => rtrim(parse_url($index, PHP_URL_PATH) ?: '/', '/'),
            ...($entry === null
                ? ['previous' => null, 'next' => null]
                : $this->changeLogs->adjacent($entry->slug)),
        ]);
    }
}
