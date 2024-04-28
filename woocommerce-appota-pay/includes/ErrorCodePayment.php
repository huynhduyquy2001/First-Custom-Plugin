<?php


class ErrorCodePayment
{
    public const SUCCESS_CODE = 0; // Warning: don't change this value
    public const ERR_CODE_INVALID_PARAMS = 1;
    public const ERR_CODE_INVALID_SIGNATURE = 2;
    public const ERR_CODE_PARTNER_NOT_FOUND = 11;
    public const ERR_CODE_PARTNER_HAS_BLOCKED = 13;
    public const ERR_CODE_PARTNER_APIKEY_NFOUND = 14;
    public const ERR_CODE_APP_NOT_ACTIVE = 15;

    public const ERR_CODE_OTP_INVALID = 21;
    public const ERR_CODE_OTP_WAS_EXPIRED = 22;
    public const ERR_CODE_DUPLICATE_ORDER = 30;
    public const ERR_CODE_DUPLICATE_TRANSACTION = 31;
    public const ERR_CODE_INVALID_AMOUNT = 32;
    public const ERR_CODE_TRANS_ERROR = 33;
    public const ERR_CODE_TRANS_PROCESSING = 34;
    public const ERR_CODE_TRANS_PENDING = 35;
    public const ERR_CODE_TRANS_NOT_FOUND = 36;
    public const ERR_CODE_TRANSACTION_UNDEFINED = 37;
    public const ERR_CODE_TRANS_CANCELLED = 38;
    public const ERR_CODE_TRANS_SEND_MAX_OTP = 39;
    public const ERR_CODE_TRAN_WAS_REFUNDED = 40;
    public const ERR_CODE_TRAN_WAS_SUCCESS = 41;

    public const ERR_CODE_BANK_CODE_NOT_FOUND = 51;
    public const ERR_CODE_PAY_METHOD_NOT_VALID = 52;

    public const ERR_CODE_TRAN_DENIED = 61;
    public const ERR_CODE_BANK_MAINTAINING = 62;
    public const ERR_CODE_CARD_NOT_REGISTERED = 63;
    public const ERR_CODE_CARD_TYPE_NOT_SUPPORT = 64;
    public const ERR_CODE_ACC_INFO_INVALID = 65;
    public const ERR_CODE_CARD_IS_RESTRICTED = 66;
    public const ERR_CODE_CARD_OPEN_DATE_INVALID = 67;
    public const ERR_CODE_CARD_WAS_BLOCKED = 68;
    public const ERR_CODE_CARD_WAS_EXPIRED = 69;
    public const ERR_CODE_EXCEED_LIMIT_DAILY = 70;
    public const ERR_CODE_CARD_HOLDER_NAME_INVALID = 71;
    public const ERR_CODE_CARD_EXPIRY_DATE_INVALID = 72;
    public const ERR_CODE_CARD_BANK_NOT_RESPONSE = 73;
    public const ERR_CODE_CARD_NOT_ENOUGH_MONEY_OR_EXCEED_LIMIT_MONEY_DAY = 74;
    public const ERR_CODE_CARD_NOT_ENOUGH_MONEY = 75;
    public const ERR_CODE_CARD_NUMBER_INVALID = 76;
    public const ERR_CODE_TRANSACTION_TIME_OUT = 77;
    public const ERR_CODE_BANK_SYSTEM_ERROR = 78;
    public const ERR_CODE_CAN_NOT_PROCESS_CARD = 79;
    public const ERR_CODE_CAND_CVV_INVALID = 80;

    public const ERR_CODE_SYSTEM_BUSY = 91;
    public const ERR_CODE_IP_DENIED = 92;
    public const ERR_CODE_SYSTEM_MAINTAINING = 94;
    public const ERR_CODE_UNKNOWN = 99;

    public const ERR_CODE_SERVER_ERROR = 500;

    public const LANG = 'vi';

    public const HTTP_CODE_BAD_REQUEST = 400;
    public const HTTP_CODE_UNAUTHORIZE = 401;
    public const HTTP_CODE_REQUEST_FAIL = 402;
    public const HTTP_CODE_REQUEST_DENIED = 403;
    public const HTTP_CODE_NOT_FOUND = 404;
    public const HTTP_CODE_CONFLICT_REQUEST = 409;
    public const HTTP_CODE_INTERNAL_ERROR = 500;

    public const ERR_CODE_BANK_CODE_EXIST = 101;
    public const ERR_STATUS_NOT_SUCCESS = 102;
    public const ERR_REFUND_OVER_AMOUNT_PARAMS = 103;

    const ERR_PARTNER_EXIST = 169;

    /*
     * function get message by error code
     */
    public static function getMessage($errorCode)
    {
        $errors = [
            self::SUCCESS_CODE => [
                'vi' => 'Thành công',
                'en' => 'Thành công'
            ],

            self::ERR_CODE_INVALID_PARAMS => [
                'vi' => 'Thông tin yêu cầu thiếu hoặc không hợp lệ',
                'en' => 'Missing or Invalid Params'
            ],

            self::ERR_CODE_INVALID_SIGNATURE => [
                'vi' => 'Signature không hợp lệ',
                'en' => 'Signature invalid'
            ],

            self::ERR_CODE_PARTNER_NOT_FOUND => [
                'vi' => 'Partner không tồn tại',
                'en' => 'Partner not found'
            ],

            self::ERR_CODE_PARTNER_HAS_BLOCKED => [
                'vi' => 'Partner đã bị khoá',
                'en' => 'Partner đã bị khoá'
            ],

            self::ERR_CODE_PARTNER_APIKEY_NFOUND => [
                'vi' => 'API Key ko tồn tại',
                'en' => 'API Key ko tồn tại'
            ],

            self::ERR_CODE_APP_NOT_ACTIVE => [
                'vi' => 'API Key chưa được kích hoạt hoặc đã bị khoá',
                'en' => 'API Key chưa được kích hoạt hoặc đã bị khoá'
            ],

            self::ERR_CODE_OTP_INVALID => [
                'vi' => 'Mã OTP không đúng',
                'en' => 'OTP invalid'
            ],

            self::ERR_CODE_OTP_WAS_EXPIRED => [
                'vi' => 'Mã OTP đã hết hạn xác thực, vui lòng thực hiện lại giao dịch',
                'en' => 'OTP was expired'
            ],

            self::ERR_CODE_DUPLICATE_ORDER => [
                'vi' => 'Mã đơn hàng đã bị trùng',
                'en' => 'Duplicate orderId'
            ],

            self::ERR_CODE_DUPLICATE_TRANSACTION => [
                'vi' => 'Mã giao dịch bị trùng',
                'en' => 'Duplicate Transaction ID'
            ],

            self::ERR_CODE_INVALID_AMOUNT => [
                'vi' => 'Số tiền không hợp lệ',
                'en' => 'Số tiền không hợp lệ'
            ],

            self::ERR_CODE_TRANS_ERROR => [
                'vi' => 'Giao dịch không thành công',
                'en' => 'Giao dịch không thành công'
            ],

            self::ERR_CODE_TRANS_PROCESSING => [
                'vi' => 'Giao dịch đang được xử lý, vui lòng kiểm tra lại sau',
                'en' => 'Giao dịch đang được xử lý, vui lòng kiểm tra lại sau'
            ],

            self::ERR_CODE_TRANS_PENDING => [
                'vi' => 'Giao dịch đang chờ xử lý, vui lòng kiểm tra lại sau',
                'en' => 'Giao dịch đang chờ xử lý, vui lòng kiểm tra lại sau'
            ],

            self::ERR_CODE_TRANS_NOT_FOUND => [
                'vi' => 'Giao dịch không tồn tại',
                'en' => 'Giao dịch không tồn tại'
            ],

            self::ERR_CODE_TRANSACTION_UNDEFINED => [
                'vi' => 'Giao dịch không xác định',
                'en' => 'Giao dịch không xác định'
            ],

            self::ERR_CODE_TRANS_CANCELLED => [
                'vi' => 'Người dùng huỷ giao dịch',
                'en' => 'Người dùng huỷ giao dịch'
            ],

            self::ERR_CODE_TRANS_SEND_MAX_OTP => [
                'vi' => 'Người dùng nhập sai mã OTP quá số lần quy định',
                'en' => 'Người dùng nhập sai mã OTP quá số lần quy định'
            ],

            self::ERR_CODE_TRAN_WAS_REFUNDED => [
                'vi' => 'Giao dịch đã được hoàn trả',
                'en' => 'Giao dịch đã được hoàn trả'
            ],

            self::ERR_CODE_TRAN_WAS_SUCCESS => [
                'vi' => 'Giao dịch đã thành công trước đó',
                'en' => 'Giao dịch đã thành công trước đó'
            ],

            self::ERR_CODE_BANK_CODE_NOT_FOUND => [
                'vi' => 'Mã ngân hàng không tồn tại',
                'en' => 'Bank code not exist'
            ],

            self::ERR_CODE_PAY_METHOD_NOT_VALID => [
                'vi' => 'Phương thức thanh toán không hợp lệ',
                'en' => 'Payment method invalid'
            ],

            self::ERR_CODE_TRAN_DENIED => [
                'vi' => 'Giao dịch bị từ chối',
                'en' => 'Giao dịch bị từ chối'
            ],

            self::ERR_CODE_BANK_MAINTAINING => [
                'vi' => 'Ngân hàng đang bảo trì, vui lòng thử lại sau',
                'en' => 'Ngân hàng đang bảo trì, vui lòng thử lại sau'
            ],

            self::ERR_CODE_CARD_NOT_REGISTERED => [
                'vi' => 'Thẻ chưa kích hoạt hoặc chưa đăng ký thanh toán online/IB',
                'en' => 'Thẻ chưa kích hoạt hoặc chưa đăng ký thanh toán online/IB'
            ],

            self::ERR_CODE_CARD_TYPE_NOT_SUPPORT => [
                'vi' => 'Loại thẻ chưa được hỗ trợ',
                'en' => 'Loại thẻ chưa được hỗ trợ'
            ],

            self::ERR_CODE_ACC_INFO_INVALID => [
                'vi' => 'Thông tin thẻ/tài khoản không đúng',
                'en' => 'Thông tin thẻ/tài khoản không đúng'
            ],

            self::ERR_CODE_CARD_IS_RESTRICTED => [
                'vi' => 'Thẻ của bạn đang bị hạn chế, vui lòng liên hệ ngân hàng để biết thông tin chi tiết',
                'en' => 'Card is restricted'
            ],

            self::ERR_CODE_CARD_OPEN_DATE_INVALID => [
                'vi' => 'Thông tin ngày mở thẻ không đúng',
                'en' => 'Card open date is wrong'
            ],

            self::ERR_CODE_CARD_WAS_BLOCKED => [
                'vi' => 'Thẻ của bạn đã bị khoá',
                'en' => 'Card was blocked'
            ],

            self::ERR_CODE_CARD_WAS_EXPIRED => [
                'vi' => 'Thẻ của bạn đã hết hạn/bị khoá',
                'en' => 'Card was expired or blocked'
            ],

            self::ERR_CODE_EXCEED_LIMIT_DAILY => [
                'vi' => 'Bạn đã giao dịch vượt quá giới hạn cho phép trong ngày, vui lòng thử lại sau',
                'en' => 'You have transacted exceeding the allowed limit for the day, please try again later'
            ],

            self::ERR_CODE_CARD_HOLDER_NAME_INVALID => [
                'vi' => 'Thông tin tên chủ thẻ không đúng',
                'en' => 'Card holder name invalid'
            ],

            self::ERR_CODE_CARD_NUMBER_INVALID => [
                'vi' => 'Số thẻ không đúng.',
                'en' => 'Card number invalid'
            ],

            self::ERR_CODE_CARD_BANK_NOT_RESPONSE => [
                'vi' => 'Cổng thanh toán không nhận được kết quả trả về từ ngân hàng phát hành thẻ',
                'en' => 'The Payment Gateway did not receive the results returned from the card issuing bank'
            ],

            self::ERR_CODE_CARD_EXPIRY_DATE_INVALID => [
                'vi' => 'Thông tin ngày phát hành/Hết hạn thẻ không đúng',
                'en' => 'Card Issue Date/Expiry Date invalid'
            ],

            self::ERR_CODE_CARD_NOT_ENOUGH_MONEY_OR_EXCEED_LIMIT_MONEY_DAY => [
                'vi' => 'Thẻ không đủ hạn mức hoặc không đủ số dư để thanh toán.',
                'en' => 'Card does not have enough limit or the account has insufficient balance to pay.'
            ],

            self::ERR_CODE_CARD_NOT_ENOUGH_MONEY => [
                'vi' => 'Số tiền không đủ để thanh toán',
                'en' => 'Card does not have enough money.'
            ],

            self::ERR_CODE_TRANSACTION_TIME_OUT => [
                'vi' => 'Quá thời gian thanh toán',
                'en' => 'Transaction is timeout'
            ],

            self::ERR_CODE_BANK_SYSTEM_ERROR => [
                'vi' => 'Lỗi từ hệ thống ngân hàng, vui lòng thực hiện lại giao dịch',
                'en' => 'Lỗi từ hệ thống ngân hàng, vui lòng thực hiện lại giao dịch'
            ],

            self::ERR_CODE_CAN_NOT_PROCESS_CARD => [
                'vi' => 'Không thể xử lý thẻ của bạn, vui lòng liên hệ nhà cung câp để được hỗ trợ',
                'en' => 'Can\'t process your card'
            ],

            self::ERR_CODE_CAND_CVV_INVALID => [
                'vi' => 'Mã CVC/CVV không chính xác',
                'en' => 'CVC/CVV invalid'
            ],

            self::ERR_CODE_UNKNOWN => [
                'vi' => 'Lỗi không xác định, vui lòng kiểm tra lại giao dịch sau',
                'en' => 'Lỗi không xác định, vui lòng kiểm tra lại giao dịch sau'
            ],

            self::ERR_CODE_SYSTEM_BUSY => [
                'vi' => 'Hệ thống đang bận, vui lòng thử lại sau',
                'en' => 'Hệ thống đang bận, vui lòng thử lại sau'
            ],

            self::ERR_CODE_IP_DENIED => [
                'vi' => 'IP không được phép truy cập',
                'en' => 'IP không được phép truy cập'
            ],

            self::HTTP_CODE_UNAUTHORIZE => [
                'vi' => 'UnAuthorized',
                'en' => 'UnAuthorized'
            ],

            self::ERR_CODE_SYSTEM_MAINTAINING => [
                'vi' => 'Hệ thống đang bảo trì, vui lòng thử lại sau',
                'en' => 'Hệ thống đang bảo trì, vui lòng thử lại sau'
            ],

            self::ERR_CODE_SERVER_ERROR => [
                'vi' => 'Hệ thống gặp lỗi, vui lòng thử lại sau',
                'en' => 'Server error'
            ],

            self::ERR_CODE_BANK_CODE_EXIST => [
                'vi' => 'Bankcode này đã tồn tại',
                'en' => 'Bankcode này đã tồn tại'
            ],

            self::ERR_STATUS_NOT_SUCCESS => [
                'vi' => 'Trạng thái giao dịch phải là success',
                'en' => 'Trạng thái giao dịch phải là success'
            ],

            self::ERR_REFUND_OVER_AMOUNT_PARAMS => [
                'vi' => 'Số tiền refund không được quá số tiền giao dịch',
                'en' => 'Số tiền refund không được quá số tiền giao dịch'
            ],

            self::ERR_PARTNER_EXIST => [
                'vi' => 'Partner code đã tồn tại',
                'en' => 'Partner code đã tồn tại'
            ],

            self::ERR_CODE_PARTNER_NOT_FOUND => [
                'vi' => 'Partner không tồn tại',
                'en' => 'Partner not found'
            ],
        ];




        return $errors[$errorCode];
    }

    public static function isErrCodeExits($errorCode)
    {
        $allErCode = [
            self::SUCCESS_CODE,
            self::ERR_CODE_INVALID_PARAMS,
            self::ERR_CODE_INVALID_SIGNATURE,
            self::ERR_CODE_PARTNER_NOT_FOUND,
            self::ERR_CODE_PARTNER_HAS_BLOCKED,
            self::ERR_CODE_PARTNER_APIKEY_NFOUND,
            self::ERR_CODE_APP_NOT_ACTIVE,
            self::ERR_CODE_OTP_INVALID,
            self::ERR_CODE_OTP_WAS_EXPIRED,
            self::ERR_CODE_DUPLICATE_ORDER,
            self::ERR_CODE_DUPLICATE_TRANSACTION,
            self::ERR_CODE_INVALID_AMOUNT,
            self::ERR_CODE_TRANS_ERROR,
            self::ERR_CODE_TRANS_PROCESSING,
            self::ERR_CODE_TRANS_PENDING,
            self::ERR_CODE_TRANS_NOT_FOUND,
            self::ERR_CODE_TRANSACTION_UNDEFINED,
            self::ERR_CODE_TRANS_CANCELLED,
            self::ERR_CODE_TRANS_SEND_MAX_OTP,
            self::ERR_CODE_TRAN_WAS_REFUNDED,
            self::ERR_CODE_TRAN_WAS_SUCCESS,
            self::ERR_CODE_BANK_CODE_NOT_FOUND,
            self::ERR_CODE_PAY_METHOD_NOT_VALID,
            self::ERR_CODE_TRAN_DENIED,
            self::ERR_CODE_BANK_MAINTAINING,
            self::ERR_CODE_CARD_NOT_REGISTERED,
            self::ERR_CODE_CARD_TYPE_NOT_SUPPORT ,
            self::ERR_CODE_ACC_INFO_INVALID ,
            self::ERR_CODE_CARD_IS_RESTRICTED,
            self::ERR_CODE_CARD_OPEN_DATE_INVALID,
            self::ERR_CODE_CARD_WAS_BLOCKED,
            self::ERR_CODE_CARD_WAS_EXPIRED,
            self::ERR_CODE_EXCEED_LIMIT_DAILY,
            self::ERR_CODE_CARD_HOLDER_NAME_INVALID ,
            self::ERR_CODE_CARD_NUMBER_INVALID ,
            self::ERR_CODE_CARD_BANK_NOT_RESPONSE,
            self::ERR_CODE_CARD_EXPIRY_DATE_INVALID,
            self::ERR_CODE_CARD_NOT_ENOUGH_MONEY_OR_EXCEED_LIMIT_MONEY_DAY,
            self::ERR_CODE_CARD_NOT_ENOUGH_MONEY,
            self::ERR_CODE_TRANSACTION_TIME_OUT,
            self::ERR_CODE_BANK_SYSTEM_ERROR,
            self::ERR_CODE_CAN_NOT_PROCESS_CARD,
            self::ERR_CODE_CAND_CVV_INVALID,
            self::ERR_CODE_UNKNOWN,
            self::ERR_CODE_SYSTEM_BUSY,
            self::ERR_CODE_IP_DENIED,
            self::HTTP_CODE_UNAUTHORIZE,
            self::ERR_CODE_SYSTEM_MAINTAINING,
            self::ERR_CODE_SERVER_ERROR,
            self::ERR_CODE_BANK_CODE_EXIST,
            self::ERR_STATUS_NOT_SUCCESS,
            self::ERR_REFUND_OVER_AMOUNT_PARAMS,
            self::ERR_PARTNER_EXIST,
            self::ERR_CODE_PARTNER_NOT_FOUND,
        ];




        return $allErCode;
    }




}