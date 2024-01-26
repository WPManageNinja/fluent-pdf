<template>
    <div class="ff_card" :class="border ? 'ff_card_border' : null" v-on:click="$emit('click')">
        <div v-if="img" class="ff_card_img" :class="imgClass">
            <img :src="img" alt="">
        </div>
        <slot />
    </div>
</template>

<script>
    export default {
        name: 'Card',
        props: {
            border: {
                type: Boolean
            },
            img: {
                type: String
            },
            imgClass: {
                type: String
            }
        }
    }
</script>
