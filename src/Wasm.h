typedef void *voidptr;

typedef enum
{
	ArrayFlags__noslices = 1U, // u64(1) << 0
	ArrayFlags__noshrink = 2U, // u64(1) << 1
	ArrayFlags__nogrow = 4U,   // u64(1) << 2
	ArrayFlags__nofree = 8U,   // u64(1) << 3
} ArrayFlags;

struct array
{
	voidptr data;
	int offset;
	int len;
	int cap;
	ArrayFlags flags;
	int element_size;
};

typedef struct array array;

typedef enum
{
	wasm__ValType__i32_t = 0x7f,	   // 0x7f
	wasm__ValType__i64_t = 0x7e,	   // 0x7e
	wasm__ValType__f32_t = 0x7d,	   // 0x7d
	wasm__ValType__f64_t = 0x7c,	   // 0x7c
	wasm__ValType__v128_t = 0x7b,	   // 0x7b
	wasm__ValType__funcref_t = 0x70,   // 0x70
	wasm__ValType__externref_t = 0x6f, // 0x6f
} wasm__ValType;

typedef enum
{
	wasm__NumType__i32_t = 0x7f, // 0x7f
	wasm__NumType__i64_t = 0x7e, // 0x7e
	wasm__NumType__f32_t = 0x7d, // 0x7d
	wasm__NumType__f64_t = 0x7c, // 0x7c
} wasm__NumType;

typedef enum
{
	wasm__RefType__funcref_t = 0x70,   // 0x70
	wasm__RefType__externref_t = 0x6f, // 0x6f
} wasm__RefType;

struct wasm__ConstExpression
{
	array call_patches;
	array code;
};
typedef struct wasm__ConstExpression wasm__ConstExpression;

// 帮助
array help_new_arr_str();
array help_add_arr_str(array arr, const char *str);

array help_new_arr_val_type();
array help_add_arr_val_type(array arr, wasm__ValType vtyp);

array help_new_arr_num_type();
array help_add_arr_num_type(array arr, wasm__NumType vtyp);

wasm__ValType help_val_type(const char *val);
wasm__NumType help_num_type(const char *val);
wasm__RefType help_ref_type(const char* val);

unsigned char help_str_to_u8(const char* str);
array help_str_to_u8s(const char* str);

// wasm
voidptr create_mod(void);
voidptr new_fn(voidptr mod, const char *name, array params, array results);
voidptr new_debug_fn(voidptr mod, const char *name, voidptr typ, array arg_names);
voidptr new_fn_type(array param, array results, const char *name);
void new_fn_import(voidptr mod, const char *modn, const char *name, array param, array results);
void new_fn_import_debug(voidptr mod, const char *modn, const char *name, voidptr typ);
int new_global(voidptr mod, const char *name, bool exp, wasm__ValType typ, bool is_mut, wasm__ConstExpression init);
int new_global_import(voidptr mod, const char *modn, const char *name, wasm__ValType typ, bool is_mut);
void assign_global_init(voidptr mod, int index, wasm__ConstExpression init);
void assign_memory(voidptr mod, const char *name, bool __v_export, unsigned int min, unsigned int max);
void assign_start(voidptr mod, const char *name);
void mod_commit(voidptr mod, voidptr func_ptr, bool __v_export);
bool mod_compile(const char *file, voidptr mod);
void mod_enable_debug(voidptr mod, const char *name);
int mod_new_data_segment(voidptr mod,const char *name, int pos, array data);
void mod_new_passive_data_segment(voidptr mod, char *name, array data);

void fn_i32_const(voidptr func_ptr, int val);
void fn_i64_const(voidptr func_ptr, long long val);
void fn_f32_const(voidptr func_ptr, float val);
void fn_f64_const(voidptr func_ptr, double val);

int fn_new_local(voidptr func_ptr, wasm__ValType typ);
void fn_local_get(voidptr func_ptr, int index);
int fn_new_local_named(voidptr func_ptr, wasm__ValType typ, char *name);
void fn_local_set(voidptr func_ptr, int index);
void fn_local_tee(voidptr func_ptr, int index);

void fn_global_get(voidptr func_ptr, int global);
void fn_global_set(voidptr func_ptr, int global);

void fn_add(voidptr func_ptr, wasm__NumType typ);
void fn_sub(voidptr func_ptr, wasm__NumType typ);
void fn_mul(voidptr func_ptr, wasm__NumType typ);
void fn_div(voidptr func_ptr, wasm__NumType typ, bool is_signed);
void fn_rem(voidptr func_ptr, wasm__NumType typ, bool is_signed);
void fn_abs(voidptr func_ptr, wasm__NumType typ);
void fn_neg(voidptr func_ptr, wasm__NumType typ);
void fn_ceil(voidptr func_ptr, wasm__NumType typ);
void fn_floor(voidptr func_ptr, wasm__NumType typ);
void fn_trunc(voidptr func_ptr, wasm__NumType typ);
void fn_nearest(voidptr func_ptr, wasm__NumType typ);
void fn_sqrt(voidptr func_ptr, wasm__NumType typ);
void fn_min(voidptr func_ptr, wasm__NumType typ);
void fn_max(voidptr func_ptr, wasm__NumType typ);
void fn_copysign(voidptr func_ptr, wasm__NumType typ);

void fn_b_and(voidptr func_ptr, wasm__NumType typ);
void fn_b_or(voidptr func_ptr, wasm__NumType typ);
void fn_b_xor(voidptr func_ptr, wasm__NumType typ);
void fn_b_shl(voidptr func_ptr, wasm__NumType typ);
void fn_b_shr(voidptr func_ptr, wasm__NumType typ, bool is_signed);
void fn_clz(voidptr func_ptr, wasm__NumType typ);
void fn_ctz(voidptr func_ptr, wasm__NumType typ);
void fn_popcnt(voidptr func_ptr, wasm__NumType typ);
void fn_rotl(voidptr func_ptr, wasm__NumType typ);
void fn_rotr(voidptr func_ptr, wasm__NumType typ);

void fn_eqz(voidptr func_ptr, wasm__NumType typ);
void fn_eq(voidptr func_ptr, wasm__NumType typ);
void fn_ne(voidptr func_ptr, wasm__NumType typ);
void fn_lt(voidptr func_ptr, wasm__NumType typ, bool is_signed);
void fn_gt(voidptr func_ptr, wasm__NumType typ, bool is_signed);
void fn_le(voidptr func_ptr, wasm__NumType typ, bool is_signed);
void fn_ge(voidptr func_ptr, wasm__NumType typ, bool is_signed);

void fn_cast(voidptr func_ptr, wasm__NumType from_type, bool is_signed, wasm__NumType to_type);
void fn_cast_trapping(voidptr func_ptr, wasm__NumType from_type, bool is_signed, wasm__NumType to_type);
void fn_reinterpret(voidptr func_ptr, wasm__NumType typ);
void fn_sign_extend8(voidptr func_ptr, wasm__ValType typ);
void fn_sign_extend16(voidptr func_ptr, wasm__ValType typ);
void fn_sign_extend32(voidptr func_ptr);

int fn_c_block(voidptr func_ptr, array params, array results);
int fn_c_loop(voidptr func_ptr, array params, array results);
int fn_c_if(voidptr func_ptr, array params, array results);
void fn_c_else(voidptr func_ptr, int label);
void fn_c_end(voidptr func_ptr, int label);
void fn_c_br(voidptr func_ptr, int label);
void fn_c_br_if(voidptr func_ptr, int label);
void fn_c_return(voidptr func_ptr);
void fn_c_select(voidptr func_ptr);
void fn_drop(voidptr func_ptr);
void fn_unreachable(voidptr func_ptr);
void fn_nop(voidptr func_ptr);
int fn_patch_pos(voidptr func_ptr);
void fn_patch(voidptr func_ptr, int loc, int begin);
void fn_export_name(voidptr func_ptr, const char *name);

void fn_call(voidptr func_ptr, char *name);
void fn_call_import(voidptr func_ptr, char *mod_name, char *name);

void fn_load(voidptr func_ptr, wasm__NumType typ, int align, int offset);
void fn_load8(voidptr func_ptr, wasm__NumType typ, bool is_signed, int align, int offset);
void fn_load16(voidptr func_ptr, wasm__NumType typ, bool is_signed, int align, int offset);
void fn_load32_i64(voidptr func_ptr, bool is_signed, int align, int offset);
void fn_store(voidptr func_ptr, wasm__NumType typ, int align, int offset);
void fn_store8(voidptr func_ptr, wasm__NumType typ, int align, int offset);
void fn_store16(voidptr func_ptr, wasm__NumType typ, int align, int offset);
void fn_store32_i64(voidptr func_ptr, int align, int offset);
void fn_memory_size(voidptr func_ptr);
void fn_memory_grow(voidptr func_ptr);
void fn_memory_init(voidptr func_ptr, int idx);
void fn_data_drop(voidptr func_ptr, int idx);
void fn_memory_copy(voidptr func_ptr);
void fn_memory_fill(voidptr func_ptr);

void fn_ref_null(voidptr func_ptr, wasm__RefType rt);
void fn_ref_func(voidptr func_ptr, char *name);
void fn_ref_func_import(voidptr func_ptr, char *mod_name, char *name);
void fn_ref_is_null(voidptr func_ptr, wasm__RefType rt);

wasm__ConstExpression constexpr_value_i32(int val);
wasm__ConstExpression constexpr_value_i64(long long val);
wasm__ConstExpression constexpr_value_f32(float val);
wasm__ConstExpression constexpr_value_f64(double val);
wasm__ConstExpression constexpr_value_zero(wasm__ValType v);
wasm__ConstExpression constexpr_ref_null(wasm__RefType rt);
