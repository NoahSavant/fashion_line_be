<?php

namespace App\Services;

use App\Constants\AuthenConstants\EncryptionKey;
use App\Constants\UtilConstant;
use App\Jobs\SendMailQueue;
use App\Mail\SendMail;
use Carbon\Carbon;
use Defuse\Crypto\Crypto;
use Defuse\Crypto\Key;
use Hash;

class BaseService
{
    public $model;

    public function create($data)
    {
        if (! $data) {
            return false;
        }

        return $this->model->create($data);
    }

    public function update($ids, $data)
    {
        return $this->model->whereIn('id', $ids)->update($data);
    }

    public function delete($ids)
    {
        return $this->model->destroy($ids);
    }

    public function getFirst($id)
    {
        return $this->model->where('id', $id)->first();
    }

    public function getAll($input, $query = null)
    {
        if (! $query) {
            $query = $this->model->query();
        }
        $limit = $input['limit'] ?? UtilConstant::LIMIT_RECORD;
        $column = $input['column'] ?? UtilConstant::COLUMN_DEFAULT;
        $order = $input['order'] ?? UtilConstant::ORDER_TYPE;

        $data = $query->orderBy($column, $order)->paginate($limit);

        return [
            'items' => $data->items(),
            'pagination' => $this->getPaginationData($data),
        ];
    }

    public function getPaginationData($data)
    {
        $pagination = [
            'perPage' => $data->perPage(),
            'currentPage' => $data->currentPage(),
            'lastPage' => $data->lastPage(),
            'totalRow' => $data->total(),
        ];

        return $pagination;
    }

    public function response($data, $status)
    {
        return response()->json($data, $status);
    }

    public function hash($data)
    {
        return Hash::make($data);
    }

    protected function encryptToken($data)
    {
        $key = Key::loadFromAsciiSafeString(EncryptionKey::REFRESH_KEY);
        $encryptedData = Crypto::encrypt(json_encode($data), $key);

        return $encryptedData;
    }

    protected function decryptToken($encryptedData)
    {
        $key = Key::loadFromAsciiSafeString(EncryptionKey::REFRESH_KEY);
        $decryptedData = Crypto::decrypt($encryptedData, $key);

        return json_decode($decryptedData, true);
    }

    protected function getColumn($data, $column = 'id')
    {
        $items = [];
        foreach ($data as $item) {
            $items[] = $item->$column;
        }

        return $items;
    }

    protected function includesAll($firstArray, $secondArray)
    {
        foreach ($firstArray as $item) {
            if (! in_array($item, $secondArray)) {
                return false;
            }
        }

        return true;
    }

    public function customDate($dateString)
    {
        $date = $this->getDate($dateString);
        $date->addHours(7);

        return str_replace(' ', 'T', $date->toDateTimeString());

    }

    public function addSecond($date, $seconds)
    {
        if (is_string($date)) {
            $date = $this->getDate($date);
        }
        $date->addSeconds($seconds);

        return str_replace(' ', 'T', $date->toDateTimeString());
    }

    public function getDate($dateString)
    {
        $dateString = str_replace(' ', 'T', $dateString);
        $date = Carbon::parse($dateString);

        return $date;
    }

    public function getBy($column="id", $data)
    {
        return $this->model->where($column, $data)->first();
    }

    public function sendMail($subject, $view, $data, $email) {
        SendMailQueue::dispatch($email, new SendMail($subject, $view, $data));
    }

}
