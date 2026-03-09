// get-transaction/index.ts
import { serve } from 'https://deno.land/std@0.168.0/http/server.ts'
import { createClient } from 'https://esm.sh/@supabase/supabase-js@2'

serve(async (req) => {
  try {
    const url = new URL(req.url)
    const invoice = url.searchParams.get('invoice')
    
    if (!invoice) {
      return new Response('Invoice required', { status: 400 })
    }
    
    const supabase = createClient(
      Deno.env.get('SUPABASE_URL')!,
      Deno.env.get('SUPABASE_ANON_KEY')! // Pakai anon key untuk public access
    )
    
    const { data, error } = await supabase
      .from('transactions')
      .select('customer_name, customer_phone, license_key, amount')
      .eq('invoice', invoice)
      .single()
    
    if (error || !data) {
      return new Response(JSON.stringify({ 
        customer_name: 'Pelanggan',
        customer_phone: 'Tidak tersedia',
        license_key: 'Menunggu...'
      }), {
        headers: { 'Content-Type': 'application/json' }
      })
    }
    
    return new Response(JSON.stringify(data), {
      headers: { 'Content-Type': 'application/json' }
    })
    
  } catch (error) {
    return new Response(JSON.stringify({ error: error.message }), { 
      status: 500,
      headers: { 'Content-Type': 'application/json' }
    })
  }
})
