<section id="lead-form" class="px-6 py-16">
    <div class="mx-auto max-w-3xl">
        <div class="mb-10 text-center">
            <h2 class="text-3xl font-bold">Залишити заявку</h2>
            <p class="mt-3 text-gray-600">
                Заповніть форму, і ми зв'яжемося з вами найближчим часом
            </p>
        </div>

        <div class="rounded-2xl border border-gray-200 p-8 shadow-sm">
            <form class="space-y-6">
                <div>
                    <label for="name" class="mb-2 block text-sm font-medium text-gray-700">Ім'я</label>
                    <input
                        id="name"
                        type="text"
                        placeholder="Ваше ім'я"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="phone" class="mb-2 block text-sm font-medium text-gray-700">Телефон</label>
                    <input
                        id="phone"
                        type="tel"
                        placeholder="+380..."
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none"
                    >
                </div>

                <div>
                    <label for="comment" class="mb-2 block text-sm font-medium text-gray-700">Коментар</label>
                    <textarea
                        id="comment"
                        rows="4"
                        placeholder="Коротко опишіть ваш запит"
                        class="w-full rounded-xl border border-gray-300 px-4 py-3 focus:outline-none"
                    ></textarea>
                </div>

                <button
                    type="submit"
                    class="w-full rounded-xl bg-black px-6 py-3 text-white"
                >
                    Надіслати заявку
                </button>
            </form>
        </div>
    </div>
</section>
