/**
 * Чекбокс
 * Пример вызова:
 * <fm-checkbox label="Является заявителем" v-model="is_applicant" id="field-id" :options="[[1,'Да'],[0,'Нет']]" :readonly="isCooldown"></fm-checkbox>
 */
export default {
	model: {
		prop: 'value'
		,event: 'change'
	}
	,props: {
		label: {
			type: String,
			required: true
		}
		,id: {
			type: String,
			required: true
		}
		,required: {
			type: Boolean
			,default: false
		}
		,value: {}
		,options: {
			type: Array
			,default: []
		}
		,disabled: {
			type: Boolean
			,default: false
		}
	}
	,computed: {
		singleMode: function(){
			return this.options.length === 1;
		}
	}
	,methods: {
		itemId: function(index){
			return this.id+'-'+index;
		}
		,itemVal: function(index){
			var r = this.options[parseInt(index)];
			return r ? r[0] : '';
		}
		,checked: function(index){
			var value = this.itemVal(index);
			return $.isArray(this.value)
				? this.value.indexOf(value) != -1
				: this.value === value;
		}
		,newValue: function(){
			if(this.singleMode)
				return this.itemVal($(this.$el).find('input:checkbox:checked').val());
			var arValue = [],vm = this;
			$(this.$el).find('input:checkbox:checked').each(function(){
				arValue.push(vm.itemVal($(this).val()));
			});
			return arValue;
		}
	}
	,template: '<div class="form-group row">'
		+'<div class="col-sm-3 col-form-label"><label v-if="!singleMode">{{ label }}<span v-if="required" class="text-warning">*</span></label></div>'
			+'<div class="col-sm-9">'
				+'<div class="custom-control custom-checkbox" v-for="(r,i) in options" :key="r[0]">'
					+'<input :value="i" :checked="checked(i)" :id="itemId(i)" @change="$emit(\'change\',newValue());" type="checkbox" class="custom-control-input">'
					+'<label v-if="singleMode" class="custom-control-label" :for="itemId(i)">{{ label }}</label>'
					+'<label v-else class="custom-control-label" :for="itemId(i)">{{ r[1] }}</label>'
				+'</div>'
			+'</div></div>'
}