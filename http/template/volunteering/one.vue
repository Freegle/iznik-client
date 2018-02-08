<template>
    <div class="panel panel-info">
        <div class="panel-heading">
            {{title}}
        </div>
        <div class="panel-body nopadtop">
            <div class="row">
                <div class="col-xs-12">
                    <div class="glyphicons glyphicons-info-sign inline" /><div class="inline padleft js-description">{{cappedDescription}}</div>
                </div>
            </div>
            <div class="row">
                <div class="col-xs-12">
                    <div class="glyphicons glyphicons-map-marker inline" /><div class="inline padleft">{{location}}</div>
                </div>
                <hr class="nomargin"/>
            </div>
            <div class="row text-center">
                <div class="col-xs-12">
                    <button @click="$emit('info')" class="js-info btn btn-white"><div class="glyphicon glyphicon-info-sign inline" /><div class="inline padleft">More info</div></button>
                </div>
            </div>
            <div class="row">
                <div v-if="groups.length > 0" class="topspace text-muted small text-center">Posted on {{groups[0].namedisplay}}</div>
            </div>
        </div>
    </div>
</template>

<script>
export default {
    props: {
        title: {
            required: true,
            type: String
        },
        description: {
            required: true,
            type: String
        },
        location: {
            required: true,
            type: String
        },
        groups: {
            default: () => [],
            type: Array,
        }
    },
    computed: {
        cappedDescription () {
            if (this.description && this.description.length > 100) {
                return this.description.substring(0, 100) + '...';
            } else {
                return this.description;
            }
        }
    }
};
</script>