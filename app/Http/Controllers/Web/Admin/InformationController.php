<?php

namespace App\Http\Controllers\Web\Admin;

use App\Enums\InformationFileType;
use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreInformationRequest;
use App\Http\Requests\Web\UpdateInformationRequest;
use App\Models\Information;
use App\Models\InformationFile;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Écran Informations (W6) : publiées par les commissaires (description, images ou PDF — au moins un des trois,
 * plusieurs de chaque possible, PROPOSITION TECHNIQUE au-delà du cahier), consultées par tous les rôles Web
 * autorisés. Liste publique par nature (Information::acrossCommissariats(), ARCHITECTURE §11) : ces informations
 * sont déjà exposées sans authentification par l'API (§8, D32), les journaliser à chaque consultation n'ajouterait
 * que du bruit (contrairement au contrôle national d'une moto volée, qui reste un cas sensible à auditer).
 * Modification et suppression réservées à l'auteur de son commissariat.
 */
class InformationController extends Controller
{
    private const DISK = 'public';

    public function index(): View
    {
        Gate::authorize('viewAny', Information::class);

        return view('informations.index', [
            'informations' => Information::acrossCommissariats()->with(['commissaire', 'commissariat', 'files'])->orderByDesc('published_at')->get(),
        ]);
    }

    public function store(StoreInformationRequest $request): RedirectResponse
    {
        $actor = $request->user();

        // Une information appartient toujours au commissariat de son auteur (cahier §8) : le superadmin, sans
        // institution par construction (§4.2), contourne la permission (Gate::before, D16) mais pas cette
        // contrainte structurelle — logique métier, pas juste une autorisation, donc pas de bypass possible ici.
        if ($actor->commissariat_id === null) {
            return redirect()->route('informations.index')->with('error', 'Seul un commissaire, rattaché à un commissariat, peut publier une information.');
        }

        $information = Information::create([
            'commissaire_id' => $actor->id,
            'commissariat_id' => $actor->commissariat_id,
            'description' => $request->input('description'),
            'published_at' => now(),
        ]);

        $this->attachFiles($information, $request->file('images', []), InformationFileType::Image);
        $this->attachFiles($information, $request->file('documents', []), InformationFileType::Document);

        return redirect()->route('informations.index')->with('status', 'Information publiée.');
    }

    public function update(UpdateInformationRequest $request, Information $information): RedirectResponse
    {
        $information->update(['description' => $request->input('description')]);

        $removeIds = $request->input('remove_files', []);
        if ($removeIds !== []) {
            foreach ($information->files()->whereIn('id', $removeIds)->get() as $file) {
                $this->deleteFile($file);
            }
        }

        $this->attachFiles($information, $request->file('images', []), InformationFileType::Image, $information->images()->max('position') + 1);
        $this->attachFiles($information, $request->file('documents', []), InformationFileType::Document, $information->documents()->max('position') + 1);

        return redirect()->route('informations.index')->with('status', 'Information modifiée.');
    }

    public function destroy(Information $information): RedirectResponse
    {
        Gate::authorize('delete', $information);

        foreach ($information->files as $file) {
            $this->deleteFile($file);
        }
        $information->delete();

        return redirect()->route('informations.index')->with('status', 'Information supprimée.');
    }

    /**
     * @param  list<UploadedFile>  $files
     */
    private function attachFiles(Information $information, array $files, InformationFileType $type, int $startPosition = 0): void
    {
        foreach (array_values($files) as $index => $file) {
            InformationFile::create([
                'information_id' => $information->id,
                'type' => $type,
                'path' => $file->store('informations', self::DISK),
                'position' => $startPosition + $index,
            ]);
        }
    }

    private function deleteFile(InformationFile $file): void
    {
        Storage::disk(self::DISK)->delete($file->path);
        $file->delete();
    }
}
