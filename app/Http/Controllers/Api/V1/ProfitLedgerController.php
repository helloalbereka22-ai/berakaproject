<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ProfitLedger;
use App\Http\Resources\ProfitLedgerResource;
use Illuminate\Http\Request;

class ProfitLedgerController extends Controller
{
    public function index(Request $request)
    {
        return ProfitLedgerResource::collection(ProfitLedger::paginate(25));
    }

    public function store(Request $request)
    {
        $data = $request->validate(['business_id'=>'required|integer|exists:businesses,id','source_type'=>'required|string','source_id'=>'required|integer','revenue'=>'required|numeric|min:0','cost'=>'required|numeric|min:0','profit'=>'required|numeric']);
        $profitLedger = ProfitLedger::create($data);
        return new ProfitLedgerResource($profitLedger);
    }

    public function show(ProfitLedger $profitLedger)
    {
        return new ProfitLedgerResource($profitLedger);
    }

    public function update(Request $request, ProfitLedger $profitLedger)
    {
        $data = $request->validate(['business_id'=>'required|integer|exists:businesses,id','source_type'=>'required|string','source_id'=>'required|integer','revenue'=>'required|numeric|min:0','cost'=>'required|numeric|min:0','profit'=>'required|numeric']);
        $profitLedger->update($data);
        return new ProfitLedgerResource($profitLedger);
    }

    public function destroy(ProfitLedger $profitLedger)
    {
        $profitLedger->delete();
        return response()->json(['message' => 'ProfitLedger deleted.']);
    }
}
