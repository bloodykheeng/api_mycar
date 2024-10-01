<?php

namespace App\Http\Controllers\API;

use App\Models\CarCart;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;

class CarCartController extends Controller
{
    public function index(Request $request)
    {
        $query = CarCart::with(['car' => function ($query) {
            $query->with([
                'brand',
                'photos',
                'videos',
                'type',
                'vendor',
                'createdBy',
                'updatedBy',
                'inspector',
                'carInspector.inspector',
                'inspectionReport' => function ($query) {
                    $query->with([
                        'CarInspectionReportCategory' => function ($query) {
                            $query->with([
                                'inspectionFieldCategory',
                                'fields' => function ($query) {
                                    $query->with([
                                        'inspectionField',
                                        'creator'
                                    ]);
                                }
                            ]);
                        },
                        'car',
                        'creator',
                        'updater'
                    ]);
                }
            ]);
        }, 'createdBy', 'updatedBy']);

        // Optional: filter by user_id if provided
        if ($request->has('user_id')) {
            $carCarts = $query->where('created_by', $request->user_id)->get();
            return response()->json($carCarts->pluck('car'));
        }

        $carCarts = $query->get();
        return response()->json($carCarts);
    }

    public function show($id)
    {
        $carCart = CarCart::with(['car', 'createdBy', 'updatedBy'])->find($id);
        if (!$carCart) {
            return response()->json(['message' => 'Car Cart not found'], 404);
        }
        return response()->json($carCart);
    }



    public function syncCarCarts(Request $request)
    {
        $carCartsData = $request->validate([
            'car_carts' => 'array',
            'car_carts.*.car_id' => 'required|exists:cars,id',
            'car_carts.*.selected_quantity' => 'required|integer|min:1',
            'car_carts.*.price' => 'required|numeric|min:0',
        ]);

        DB::beginTransaction();
        try {


            if (!empty($carCartsData['car_carts'])) {
                $results = [];

                foreach ($carCartsData['car_carts'] as $data) {
                    $data['created_by'] = $data['updated_by'] = auth()->id();

                    $carCarts = CarCart::updateOrCreate(
                        [
                            'car_id' => $data['car_id'],
                            'created_by' => $data['created_by']
                        ],
                        $data
                    );
                    // Dynamically load the detailed car information and its nested relationships
                    $carCarts->load([
                        'car' => function ($query) {
                            $query->with([
                                'brand',
                                'photos',
                                'videos',
                                'type',
                                'vendor',
                                'createdBy',
                                'updatedBy',
                                'inspector',
                                'carInspector.inspector',
                                'inspectionReport' => function ($query) {
                                    $query->with([
                                        'CarInspectionReportCategory' => function ($query) {
                                            $query->with([
                                                'inspectionFieldCategory',
                                                'fields' => function ($query) {
                                                    $query->with('inspectionField', 'creator');
                                                }
                                            ]);
                                        },
                                        'car',
                                        'creator',
                                        'updater'
                                    ]);
                                }
                            ]);
                        },
                        'createdBy',
                        'updatedBy'
                    ]);
                    // $results[] = $data;
                    $carDetails = $carCarts->car ? $carCarts->car->toArray() : [];
                    $results[] = array_merge($carDetails, [
                        'car_id' => $carCarts->car_id,
                        'selected_quantity' => $carCarts->selected_quantity,
                        'price' => $carCarts->price,
                    ]);
                }
            } elseif (empty($carCartsData['car_carts'])) {
                $carCarts = CarCart::where('created_by', auth()->id())
                    ->with([
                        'car' => function ($query) {
                            $query->with([
                                'brand',
                                'photos',
                                'videos',
                                'type',
                                'vendor',
                                'createdBy',
                                'updatedBy',
                                'inspector',
                                'carInspector.inspector',
                                'inspectionReport' => function ($query) {
                                    $query->with([
                                        'CarInspectionReportCategory' => function ($query) {
                                            $query->with([
                                                'inspectionFieldCategory',
                                                'fields' => function ($query) {
                                                    $query->with('inspectionField', 'creator');
                                                }
                                            ]);
                                        },
                                        'car',
                                        'creator',
                                        'updater'
                                    ]);
                                }
                            ]);
                        },
                        'createdBy',
                        'updatedBy'
                    ])
                    ->get();

                $results = $carCarts->map(function ($carCart) {
                    $carDetails = $carCart->car ? $carCart->car->toArray() : [];
                    return array_merge($carDetails, [
                        'car_id' => $carCart->car_id,
                        'selected_quantity' => $carCart->selected_quantity,
                        'price' => $carCart->price,
                    ]);
                });

                return response()->json(['message' => 'Car carts retrieved successfully', 'data' => $results], 200);
            }
            DB::commit();
            return response()->json(['message' => 'Car carts synchronized successfully', 'data' => $results], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error synchronizing car carts', 'error' => $e->getMessage()], 500);
        }
    }


    public function store(Request $request)
    {
        $validated = $request->validate([
            'car_id' => 'required|exists:cars,id',
            'selected_quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $validated['created_by'] = auth()->user()->id;
        $validated['updated_by'] = auth()->user()->id;

        DB::beginTransaction();
        try {
            $carCart = new CarCart($validated);
            $carCart->save();

            DB::commit();
            return response()->json(['message' => 'Car Cart created successfully', 'data' => $carCart], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error creating Car Cart', 'error' => $e->getMessage()], 500);
        }
    }

    public function update(Request $request, $id)
    {

        // $carCart = CarCart::find($id);
        $carCart = CarCart::where('car_id', $id)
            ->where('created_by', auth()->user()->id)
            ->first();
        if (!$carCart) {
            return response()->json(['message' => 'Car Cart not found'], 404);
        }

        $validated = $request->validate([
            'car_id' => 'sometimes|required|exists:cars,id',
            'selected_quantity' => 'required|integer|min:1',
            'price' => 'required|numeric|min:0',
        ]);

        $validated['updated_by'] = auth()->user()->id;

        DB::beginTransaction();
        try {
            $carCart->update($validated);

            DB::commit();
            return response()->json(['message' => 'Car Cart updated successfully', 'data' => $carCart]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json(['message' => 'Error updating Car Cart', 'error' => $e->getMessage()], 500);
        }
    }

    public function destroy($id)
    {
        // $carCart = CarCart::find($id);
        $carCart = CarCart::where('car_id', $id)
            ->where('created_by', auth()->user()->id)
            ->first();
        if (!$carCart) {
            return response()->json(['message' => 'Car Cart not found'], 404);
        }

        $carCart->delete();
        return response()->json(['message' => 'Car Cart deleted successfully'], 204);
    }
}
