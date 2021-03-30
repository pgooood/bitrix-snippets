/**
 * multi-input
 * Поле ввода множественных значений
 * Пример вызова:
 * <fm-multi-input label="Телефон" v-model="phones" type="tel" :required="true"></fm-multi-input>
 */
export default {
	props: {
		label: {
			type: String,
			required: true
		}
		,required: {
			type: Boolean
			,default: false
		}
		,value: {
			type: Array
			,default: []
		}
		,type: {
			type: String
			,default: 'text'
		}
		,readonly: {
			type: Boolean
			,default: false
		}
		,disabled: {
			type: Boolean
			,default: false
		}
	}
	,computed: {
		inputListeners: function(){
			return Object.assign({}
				,this.$listeners
				,{input: $.proxy(function(event){
						this.input(event);
					},this)}
			);
		}
	}
	,methods: {
		copyValue: function(){
			var value = [];
			for(i = 0; i < this.value.length; i++)
				value[i] = this.value[i];
			return value;
		}
		,add: function(){
			var value = this.copyValue();
			value.push('');
			this.$emit('input',value);
		}
		,remove: function(index){
			var value = this.copyValue();
			value.splice(index,1);
			this.$emit('input',value);
		}
		,input: function(event){
			var value = this.copyValue()
				,$input = $(event.target)
				,index = parseInt($input.data('index'));
			value[index] = $input.val();
			this.$emit('input',value);
		}
	}
	,mounted: function(){
		if(!this.value.length)
			this.add();
	}
	,template: '<div class="form-group row">'
			+'<div class="col-sm-3 col-form-label">{{ label }}<span v-if="required" class="text-warning">*</span></div>'
			+'<div class="col-sm-9">'
				+'<div class="input-group mb-2" v-for="(v,index) in value">'
					+'<input class="form-control" :type="type" :required="required" :readonly="readonly" :disabled="disabled" :value="v" :data-index="index" v-on="inputListeners">'
					+'<div class="input-group-append">'
						+'<button type="button" class="btn btn-link" @click="add()" :disabled="readonly || disabled" v-if="index === value.length - 1"><i class="fas fa-plus"></i></button>'
						+'<button type="button" class="btn btn-link" @click="remove(index)" :disabled="readonly || disabled" v-if="value.length > 1"><i class="fas fa-minus"></i></button>'
		+'</div></div></div></div>'
}