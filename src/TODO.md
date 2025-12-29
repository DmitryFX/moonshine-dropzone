##DZ lib:
	- maxFiles issue: not taking into accoount previously uploaded files
	- correctly process situation when file is not accepted due to maxFiles, but if other file is removed, not accepted file can be uploaded.
		maybe add error reason field.
	- thumbnailing unrenderable files
	- strange thumbnail canvas behavior. seems it's shared between instances.


##DZ Field:
	- add basic photo editor |
	- Better handling of 'accepted' state. E.g. setting rejection reason | +
	- make coverMode() / posterMode() | +
	- add sorting
	- better error reporting and note/instruction message
	- !!!! Possible bug in afterApply - data_set may rewrite nested json fields with the same values




		