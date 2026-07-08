<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreImportRequest;
use App\Jobs\ImportArtistsJob;
use App\Models\Import;
use App\Models\ImportRow;
use App\Services\ArtistImportParser;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class ImportController extends Controller
{
    public function __construct(
        protected ArtistImportParser $parser
    ) {}

    public function index(Request $request): View
    {
        $this->authorize('viewAny', Import::class);

        $imports = Import::with('user')
            ->when(! $request->user()->can('imports.create'), function ($query) use ($request) {
                $query->where('user_id', $request->user()->id);
            })
            ->latest()
            ->paginate(15);

        return view('imports.index', compact('imports'));
    }

    public function create(): View
    {
        $this->authorize('create', Import::class);

        return view('imports.create');
    }

    public function store(StoreImportRequest $request): RedirectResponse
    {
        $this->authorize('create', Import::class);

        $file = $request->file('file');
        $result = $this->parser->parse($file);

        $storedPath = $file->store('imports');

        $import = Import::create([
            'user_id' => $request->user()->id,
            'original_filename' => $file->getClientOriginalName(),
            'stored_filename' => basename($storedPath),
            'status' => Import::STATUS_PENDING,
            'headers' => $result['headers'],
            'total_rows' => count($result['rows']),
        ]);

        if (! $result['valid']) {
            $import->update([
                'status' => Import::STATUS_FAILED,
                'validation_errors' => $result['errors'],
            ]);

            Storage::delete($storedPath);

            return redirect()
                ->route('imports.create')
                ->with('error', implode(' ', $result['errors']));
        }

        foreach ($result['rows'] as $row) {
            $import->rows()->create([
                'row_number' => $row['row_number'],
                'raw_data' => $row['data'],
                'status' => ImportRow::STATUS_PENDING,
            ]);
        }

        return redirect()
            ->route('imports.show', $import)
            ->with('success', 'Archivo cargado. Revisa el preview antes de procesar.');
    }

    public function show(Import $import): View
    {
        $this->authorize('view', $import);

        $rows = $import->rows()->orderBy('row_number')->simplePaginate(50);

        return view('imports.show', compact('import', 'rows'));
    }

    public function process(Import $import): RedirectResponse
    {
        $this->authorize('process', $import);

        if (! $import->isPending()) {
            return redirect()
                ->route('imports.show', $import)
                ->with('error', 'Esta importación ya fue procesada o falló.');
        }

        ImportArtistsJob::dispatch($import);

        return redirect()
            ->route('imports.show', $import)
            ->with('success', 'La importación se ha encolado para procesamiento.');
    }
}
