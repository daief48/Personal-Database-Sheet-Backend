<?php

namespace App\Http\Controllers;

use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use App\Models\Promotion;
use App\Repositories\ResponseRepository;
use Illuminate\Http\Response;
use Validator;
use File;


class PromotionController extends Controller
{

    protected $responseRepository;
    public function __construct(ResponseRepository $rp)
    {
        //$this->middleware('auth:api', ['except' => []]);
        $this->responseRepository = $rp;
    }

    /**
     * @OA\Get(
     * tags={"PDS User Promotion"},
     * path= "/pds-backend/api/getPromotion",
     * operationId="getPromotion",
     * summary="Promotion List",
     * description="Total Promotion List",
     * @OA\Response(response=200, description="Success" ),
     * @OA\Response(response=400, description="Bad Request"),
     * @OA\Response(response=404, description="Resource Not Found"),
     * ),
     * security={{"bearer_token":{}}}
     */

    public function getPromotion()
    {
        try {


            $getPromotion = Promotion::leftJoin('employees', 'employees.id', '=', 'promotions.employee_id')
                ->leftJoin('designations as to_designation', 'promotions.to_designation', '=', 'to_designation.id')
                ->leftJoin('designations as from_designation', 'promotions.from_designation', '=', 'from_designation.id')
                ->leftJoin('departments as to_department', 'promotions.to_department', '=', 'to_department.id')
                ->leftJoin('departments as from_department', 'promotions.from_department', '=', 'from_department.id')
                ->leftJoin('offices as to_office', 'promotions.to_office', '=', 'to_office.id')
                ->leftJoin('offices as from_office', 'promotions.from_office', '=', 'from_office.id')
                ->select(
                    'promotions.*',
                    'employees.name as employee_name',
                    'to_office.office_name as to_office_title',
                    'from_office.office_name as from_office_title',
                    'to_department.dept_name as to_department_title',
                    'from_department.dept_name as from_department_title',
                    'to_designation.designation_name as to_designation_title',
                    'from_designation.designation_name as from_designation_title',
                    'promotions.promotion_date',
                );
            // ->orderBy('id', 'desc')->get();

            $userRole = Auth::user()->role_id;
            if ($userRole == 1) {
                $getPromotion = $getPromotion->orderBy('id', 'desc')->get();
            } else {
                $employeeInfo = Employee::where('user_id', Auth::user()->id)->first();
                $getPromotion = $getPromotion->where('promotions.employee_id', $employeeInfo->id)->get();
            }

            return response()->json([
                'status' => 'success',
                'list' => $getPromotion,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 401);
        }
    }

    /**
     * @OA\Post(
     * tags={"PDS User Promotion"},
     * path="/pds-backend/api/addPromotion",
     * operationId="addPromotion",
     * summary="Add New Promotion",
     * description="Add New Promotion",
     *     @OA\RequestBody(
     *         @OA\JsonContent(),
     *         @OA\MediaType(
     *            mediaType="multipart/form-data",
     *            @OA\Schema(
     *               type="object",
     *               required={},
     *               @OA\Property(property="employee_id", type="integer",example=1),
     *               @OA\Property(property="promotion_ref_number", type="text"),
     *               @OA\Property(property="to_office", type="text"),
     *               @OA\Property(property="from_office", type="text"),
     *               @OA\Property(property="to_department", type="text"),
     *               @OA\Property(property="from_department", type="text"),
     *               @OA\Property(property="to_designation", type="text"),
     *               @OA\Property(property="from_designation", type="text"),
     *               @OA\Property(property="promotion_date", type="date"),
     *               @OA\Property(property="status", type="text"),
     *               @OA\Property(property="description", type="text")
     *            ),
     *        ),
     *    ),
     *      @OA\Response(
     *          response=200,
     *          description="Added Promotion Record Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * ),
     *     security={{"bearer_token":{}}}
     */



    public function addPromotion(Request $request)
    {
        try {
            $rules = [
                // 'employee_id' => 'required',
                'promotion_ref_number' => 'required',
                // 'to_office' => 'required',
                // 'from_office' => 'required',
                // 'to_department' => 'required',
                // 'from_department' => 'required',
                // 'to_designation' => 'required',
                // 'from_designation' => 'required',
                'promotion_date' => 'required',
                'description' => 'required',

            ];

            $messages = [
                // 'employee_id.required' => 'The employee_id field is required',
                'promotion_ref_number.required' => 'The promotion_ref_number field is required',
                // 'to_office.required' => ' The to_office field is required',
                // 'from_office.required' => 'The from_office field is required',
                // 'to_department.required' => 'The to_department field is required',
                // 'from_department.required' => 'The from_department field is required',
                // 'to_designation.required' => 'The to_designation field is required',
                // 'from_designation.required' => 'The from_designation field is required',
                'promotion_date.required' => 'The promotion_date field is required',
                'description.required' => 'The description field is required',

            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return $this->responseRepository->ResponseError(null, $validator->errors(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }


            $promotion = Promotion::create([
                'employee_id' => $request->employee_id,
                'promotion_ref_number' => $request->promotion_ref_number,
                'to_office' => $request->to_office,
                'from_office' => $request->from_office,
                'to_department' => $request->to_department,
                'from_department' => $request->from_department,
                'to_designation' => $request->to_designation,
                'from_designation' => $request->from_designation,
                'promotion_date' => $request->promotion_date,
                'description' => $request->description,
                'status' => $request->status ?? 0,
            ]);

            return response()->json([
                'status'  => true,
                'message' => "Promotion Created Successfully",
                'errors'  => null,
                'data'    => $promotion,
            ], 200);
        } catch (\Exception $e) {
            return $this->responseRepository->ResponseError("Error", $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @OA\Put(
     * tags={"PDS User Promotion"},
     * path="/pds-backend/api/updatePromotion/{id}",
     * operationId="updatePromotion",
     * summary="Update Promotion Record",
     * @OA\Parameter(name="id", description="id, eg; 1", required=true, in="path", @OA\Schema(type="integer")),
     * @OA\RequestBody(
     *          @OA\JsonContent(
     *              type="object",
     *              @OA\Property(property="employee_id", type="integer", example=1),
     *              @OA\Property(property="promotion_ref_number", type="text", example="2211"),
     *              @OA\Property(property="to_office", type="text", example=1),
     *               @OA\Property(property="from_office", type="text", example=1),
     *             @OA\Property(property="to_department", type="text", example=1),
     *             @OA\Property(property="from_department", type="text", example=1),
     *             @OA\Property(property="to_designation", type="text", example=1),
     *             @OA\Property(property="from_designation", type="text", example=1),
     *              @OA\Property(property="promotion_date", type="date", example="2023-03-23"),
     *              @OA\Property(property="description", type="text", example="good"),
     *              @OA\Property(property="status", type="integer", example=1),
     *          ),
     *      ),
     *      @OA\Response(
     *          response=200,
     *          description="Promotion Record Update Successfully",
     *          @OA\JsonContent()
     *       ),
     *      @OA\Response(response=400, description="Bad request"),
     *      @OA\Response(response=404, description="Resource Not Found"),
     * ),
     *     security={{"bearer_token":{}}}
     */


    public function updatePromotion(Request $request, $id)
    {

        try {

            $rules = [
                   // 'employee_id' => 'required',
                   'promotion_ref_number' => 'required',
                   // 'to_office' => 'required',
                   // 'from_office' => 'required',
                   // 'to_department' => 'required',
                   // 'from_department' => 'required',
                   // 'to_designation' => 'required',
                   // 'from_designation' => 'required',
                   'promotion_date' => 'required',
                   'description' => 'required',

            ];

            $messages = [
            // 'employee_id.required' => 'The employee_id field is required',
            'promotion_ref_number.required' => 'The promotion_ref_number field is required',
            // 'to_office.required' => ' The to_office field is required',
            // 'from_office.required' => 'The from_office field is required',
            // 'to_department.required' => 'The to_department field is required',
            // 'from_department.required' => 'The from_department field is required',
            // 'to_designation.required' => 'The to_designation field is required',
            // 'from_designation.required' => 'The from_designation field is required',
            'promotion_date.required' => 'The promotion_date field is required',
            'description.required' => 'The description field is required',

            ];

            $validator = Validator::make($request->all(), $rules, $messages);
            if ($validator->fails()) {
                return $this->responseRepository->ResponseError(null, $validator->errors(), Response::HTTP_INTERNAL_SERVER_ERROR);
            }

            $promotion = Promotion::findOrFail($id);
            $promotion->employee_id = $request->employee_id;
            $promotion->promotion_ref_number = $request->promotion_ref_number;
            $promotion->to_office = $request->to_office;
            $promotion->from_office = $request->from_office;
            $promotion->to_department = $request->to_department;
            $promotion->from_department = $request->from_department;
            $promotion->to_designation = $request->to_designation;
            $promotion->from_designation = $request->from_designation;
            $promotion->promotion_date = $request->promotion_date;
            $promotion->description = $request->description;
            $promotion->save();

            return response()->json([
                'status'  => true,
                'message' => "Promotion Updated Successfully",
                'errors'  => null,
                'data'    => $promotion,
            ], 200);
        } catch (\Exception $e) {
            return $this->responseRepository->ResponseError(null, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @OA\Delete(
     *     path="/pds-backend/api/deletePromotion/{id}",
     *     tags={"PDS User Promotion"},
     *     summary="Delete Promotion Record",
     *     description="Delete  Promotion Record With Valid ID",
     *     operationId="deletePromotion",
     *     @OA\Parameter(name="id", description="id", example = 1, required=true, in="path", @OA\Schema(type="integer")),
     *     @OA\Response( response=200, description="Successfully, Delete Promotion Record" ),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     * ),
     *     security={{"bearer_token":{}}}
     */

    public function deletePromotion($id)
    {
        try {
            $promotion =  Promotion::findOrFail($id);
            $promotion->delete();

            return response()->json([
                'status'  => true,
                'message' => "Promotion Record Deleted Successfully",
                'errors'  => null,
                'data'    => $promotion,
            ], 200);
        } catch (\Exception $e) {
            return $this->responseRepository->ResponseError(null, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * @OA\Patch(
     *     path="/pds-backend/api/activePromotionRecord/{id}",
     *     tags={"PDS User Promotion"},
     *     summary="Active Promotion Record",
     *     description="Active Specific Promotion Record With Valid ID",
     *     operationId="activePromotionRecord",
     *     @OA\Parameter(name="id", description="id", example = 1, required=true, in="path", @OA\Schema(type="integer")),
     *     @OA\Response( response=200, description="Successfully, Active Promotion Record" ),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     * ),
     *     security={{"bearer_token":{}}}
     */

    public function activePromotionRecord($id)
    {
        try {
            $promotionInfo =  Promotion::find($id);

            if (!($promotionInfo === null)) {
                $promotionInfo = Promotion::where('id', '=', $id)->update(['status' => 1]);
                return response()->json([
                    'status'  => true,
                    'message' => "Actived Promotion Record Successfully",
                    'errors'  => null,
                    'data'    => $promotionInfo,
                ], 200);
            } else {
                return $this->responseRepository->ResponseSuccess(null, 'Promotion Record Id Are Not Valid!');
            }
        } catch (\Exception $e) {
            return $this->responseRepository->ResponseError(null, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @OA\Patch(
     *     path="/pds-backend/api/inactivePromotionRecord/{id}",
     *     tags={"PDS User Promotion"},
     *     summary="In-active Promotion Record",
     *     description="In-active Specific Promotion Record With Valid ID",
     *     operationId="inactivePromotionRecord",
     *     @OA\Parameter(name="id", description="id", example = 1, required=true, in="path", @OA\Schema(type="integer")),
     *     @OA\Response( response=200, description="Successfully, In-active Promotion Record" ),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=404, description="Resource Not Found"),
     * ),
     *     security={{"bearer_token":{}}}
     */

    public function inactivePromotionRecord($id)
    {
        try {
            $promotionInfo =  Promotion::find($id);

            if (!($promotionInfo === null)) {
                $promotionInfo = Promotion::where('id', '=', $id)->update(['status' => 2]);
                return response()->json([
                    'status'  => true,
                    'message' => "Inactived Promotion  Record Successfully",
                    'errors'  => null,
                    'data'    => $promotionInfo,
                ], 200);
            } else {
                return $this->responseRepository->ResponseSuccess(null, 'Promotion Record Id Are Not Valid!');
            }
        } catch (\Exception $e) {
            return $this->responseRepository->ResponseError(null, $e->getMessage(), Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }


    /**
     * @OA\Get(
     * tags={"PDS User Promotion"},
     * path="/pds-backend/api/specificUserPromotion/{id}",
     * operationId="specificUserPromotion",
     * summary="Get Specific User Promotion Record",
     * description="",
     * @OA\Parameter(name="id", description="id", example = 1, required=true, in="path", @OA\Schema(type="integer")),
     * @OA\Response(response=200, description="Success" ),
     * @OA\Response(response=400, description="Bad Request"),
     * @OA\Response(response=404, description="Resource Not Found"),
     * ),
     * security={{"bearer_token":{}}}
     */

   


    /**
     * @OA\Get(
     *     tags={"PDS User Promotion"},
     *     path="/pds-backend/api/specificUserPromotionRecordByEmployeeId/{employee_id}",
     *     operationId="specificUserPromotionRecordByEmployeeId",
     *     summary="Get Specific User Training Record",
     *     description="",
     *     @OA\Parameter(
     *         name="employee_id",
     *         description="Employee ID",
     *         example=1,
     *         required=true,
     *         in="path",
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="Success"),
     *     @OA\Response(response=400, description="Bad Request"),
     *     @OA\Response(response=404, description="Not Found"),
     *     security={{"bearer_token": {}}}
     * )
     */
    public function specificUserPromotionRecordByEmployeeId(Request $request)
    {
        try {
            $getTrainingList = Promotion::leftJoin('employees', 'employees.id', '=', 'promotions.employee_id')
                ->leftJoin('designations', 'designations.id', '=', 'promotions.promoted_designation')
                ->select('employees.id as employee_id', 'employees.name as employee_name', 'promotions.*', 'designations.designation_name as designation_name')
                ->where('promotions.employee_id', $request->employee_id)
                ->get();

            return response()->json([
                'status' => 'success',
                'data' => $getTrainingList,
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage(),
            ], 404);
        }
    }
}
