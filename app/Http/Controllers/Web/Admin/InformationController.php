<?php

namespace App\Http\Controllers\Web\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Web\StoreInformationRequest;
use App\Http\Requests\Web\UpdateInformationRequest;
use App\Models\Information;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

/**
 * Écran Informations (W6) : publiées par les commissaires (description, image ou PDF — au moins un des trois),
 * consultées par tous les rôles Web autorisés. Liste publique par nature (Information::acrossCommissariats(),
 * ARCHITECTURE §11) : ces informations sont déjà exposées sans authentification par l'API (§8, D32), les
 * journaliser à chaque consultation n'ajouterait que du bruit (contrairement au contrôle national d'une moto
 * volée, qui reste un cas sensible à auditer). Modification et suppression réservées à l'auteur de son commissariat.
 */
class InformationController extends Controller
{
    private const DISK = 'public';

    public function index(): View
    {
        Gate::authorize('viewAny', Information::class);

        return view('informations.index', [
            'informations' => Information::acrossCommissariats()->with(['commissaire', 'commissariat'])->orderByDesc('published_at')->get(),
        ]);
    }

    public function store(StoreInformationRequest $request): RedirectResponse
    {
        $actor = $request->user();

        Information::create([
            'commissaire_id' => $actor->id,
            'commissariat_id' => $actor->commissariat_id,
            'description' => $request->input('description'),
            'image_path' => $request->file('image')?->store('informations', self::DISK),
            'document_path' => $request->file('document')?->store('informations', self::DISK),
            'published_at' => now(),
        ]);

        return redirect()->route('informations.index')->with('status', 'Information publiée.');
    }

    public function update(UpdateInformationRequest $request, Information $information): RedirectResponse
    {
        $data = ['description' => $request->input('description')];

        if ($request->hasFile('image')) {
            $this->deleteFile($information->image_path);
            $data['image_path'] = $request->file('image')->store('informations', self::DISK);
        } elseif ($request->boolean('remove_image')) {
            $this->deleteFile($information->image_path);
            $data['image_path'] = null;
        }

        if ($request->hasFile('document')) {
            $this->deleteFile($information->document_path);
            $data['document_path'] = $request->file('document')->store('informations', self::DISK);
        } elseif ($request->boolean('remove_document')) {
            $this->deleteFile($information->document_path);
            $data['document_path'] = null;
        }

        $information->update($data);

        return redirect()->route('informations.index')->with('status', 'Information modifiée.');
    }

    public function destroy(Information $information): RedirectResponse
    {
        Gate::authorize('delete', $information);

        $this->deleteFile($information->image_path);
        $this->deleteFile($information->document_path);
        $information->delete();

        return redirect()->route('informations.index')->with('status', 'Information supprimée.');
    }

    private function deleteFile(?string $path): void
    {
        if ($path !== null) {
            Storage::disk(self::DISK)->delete($path);
        }
    }
}
