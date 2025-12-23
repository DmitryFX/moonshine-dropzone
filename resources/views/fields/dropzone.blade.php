@php
	//$settings=json_encode(get_defined_vars());
	extract( $DZ_CFG );
	//$uid = $DZ_CFG['uid'];
	$csrf_token = csrf_token();

@endphp



<div
	x-data='Dropzone_Data(@json( $DZ_CFG ))'
	class="
		dropzone_field
		{{ $minimize_component_width ? 'minimize_component_width' : '' }}
	"
	style="
		min-width: min( {{  $thumbnail_w }}px, 100% );
	"
>
	
		<x-moonshine::form.input
			type="hidden"
			x-ref='dropzone_existing_files_field'
			:attributes="$attributes->merge([
				'value' => $value
			])->except('x-bind:id')"
		/>


		<div 
			x-ref='dropzone_dropzone'
			class="
				dropzone_dropzone 
				{{ $dropzone_grid_max_columns === 1 ? 'single_col' : '' }} 
				{{ $single_preview_maximize ? 'single_preview_maximize' : '' }}
			"
			style="
				grid-template-columns: repeat({{ $dropzone_grid_max_columns }}, minmax( 0, {{ $thumbnail_w }}px ) );
				
			" 
		>
			<div class="dz-preview dz_sizer" style="
				display: none;
				pointer-events: none;
				max-width: {{ $thumbnail_w }}px;
				aspect-ratio: {{ $thumbnail_aspect }};
			"></div>

		</div>
		
		<template x-ref='dropzone_file_preview_tpl'>

			<div
				class="dz-preview dz-file-preview" 
				style="
					max-width: {{ $thumbnail_w }}px;
					aspect-ratio: {{ $thumbnail_aspect }};
				">

					<img data-dz-thumbnail
						class="dz-thumbnail"
					/>
					<div class="file_icon" style="display: none;"></div>

					<div class="dz-remove-button" data-confirmation_pending="false">

						<x-moonshine::icon icon="trash" size="4" class="trash"/>
						<x-moonshine::icon icon="check" size="4" class="check"/>

					</div>

					<div class="dz-progress">

						<svg class="progressbar _hidden" viewBox="0 0 500 500" xmlns="http://www.w3.org/2000/svg"
							xml:space="preserve"
							style="fill-rule:evenodd;clip-rule:evenodd;stroke-linecap:cap;stroke-linejoin:round;stroke-miterlimit:1.5">
						<circle class="loading_ui_progressbar" cx="50%" cy="50%" r="45%"
								style="fill:none;stroke:#000;stroke-width:10%" pathLength="100"/>
						</svg>
							
					</div>
				<div class="dz-details" style="">
					<div class="dz-filename">
						<span data-dz-name></span>
					</div>
					<!-- <div class="dz-size" data-dz-size></div> -->

					<!-- <span class="dz-upload" data-dz-uploadprogress="" style="width: 100%;"></span>
					<div class="dz-success-mark"><span>✔</span></div>
					<div class="dz-error-mark"><span>✘</span></div>
					<div class="dz-error-message"><span data-dz-errormessage></span></div> -->
				</div>


			</div>

		</template>

	<script>

		window.addEventListener("alpine:init", () => {

			Alpine.data( 'Dropzone_Data', (settings) => ({

				init(){

					this.$nextTick( () => {

						Dropzone_Field( this, settings );
						
					});

				}

			}));

		});

		
	</script>


</div>